<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Server;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Operation::class)]
#[CoversClass(Partial\Operation::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[CoversClass(CannotSupport::class)]
#[UsesClass(Server::class)]
#[UsesClass(Partial\Server::class)]
#[UsesClass(Partial\Parameter::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
class OperationTest extends TestCase
{
    #[Test]
    public function itRequiresAnOperationId(): void
    {
        $partialOperation = PartialHelper::createOperation(operationId: null);

        self::expectException(CannotSupport::class);
        self::expectExceptionCode(CannotSupport::MISSING_OPERATION_ID);

        new Operation(new Identifier(''), [], [], Method::GET, $partialOperation);
    }

    /**
     * @param Parameter[] $expected
     * @param Parameter[] $pathParameters
     */
    #[Test, DataProvider('provideParameters')]
    public function itOverridesPathParametersOfTheSameName(
        Identifier $parentIdentifier,
        array $expected,
        array $pathParameters,
        Method $method,
        Partial\Operation $partialOperation
    ): void {
        $sut = new Operation($parentIdentifier, [], $pathParameters, $method, $partialOperation);

        self::assertEquals($expected, $sut->parameters);
    }

    #[Test]
    public function itCannotSupportConflictingParameters(): void
    {
        $parentIdentifier = new Identifier('test-path');

        $operationId = 'test-id';
        $method = Method::GET;
        $identifier = $parentIdentifier->append($operationId, $method->value);

        $parameterNames = ['param1', 'param2'];
        $parameterIdentifiers = array_map(
            fn($n) => (string)$identifier->append($n, 'query'),
            $parameterNames
        );

        $partialOperation = PartialHelper::createOperation(
            operationId: $operationId,
            parameters: [
                PartialHelper::createParameter(
                    name: $parameterNames[0],
                    in: 'query',
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'object')
                ),
                PartialHelper::createParameter(
                    name: $parameterNames[1],
                    in: 'query',
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'object')
                )
            ]
        );

        self::expectExceptionObject(CannotSupport::conflictingParameterStyles(...$parameterIdentifiers));

        new Operation($parentIdentifier, [], [], $method, $partialOperation);
    }

    #[Test, DataProvider('provideOperationsToValidate')]
    public function itValidatesOperations(
        InvalidOpenAPI $expected,
        Identifier $parentIdentifier,
        Method $method,
        Partial\Operation $partialOperation,
    ): void {
        self::expectExceptionObject($expected);

        new Operation($parentIdentifier, [], [], $method, $partialOperation);
    }

    /**
     * @param array<int,Partial\Server> $expected
     * @param array<int,Server> $pathServers
     * @param Partial\Server[] $operationServers
     */
    #[Test, DataProvider('provideServers')]
    #[TestDox('If a Path Item specifies any Servers, it overrides OpenAPI servers')]
    public function itOverridesPathLevelServers(
        array $expected,
        Identifier $parentIdentifier,
        string $operationId,
        Method $method,
        array $pathServers,
        array $operationServers,
    ): void {
        $sut = new Operation(
            $parentIdentifier,
            $pathServers,
            [],
            $method,
            PartialHelper::createOperation(
                operationId: $operationId,
                servers: $operationServers,
            )
        );

        self::assertEquals($expected, $sut->servers);
    }


    /**
     * @param Warning[] $expected
     */
    #[Test, DataProvider('provideDuplicateServers')]
    public function itWarnsAgainstDuplicateServers(
        array $expected,
        Partial\Operation $operation,
    ): void {
        $sut = new Operation(
            new Identifier('test'),
            [],
            [],
            Method::GET,
            $operation
        );

        self::assertEquals($expected, $sut
            ->getWarnings()
            ->findByWarningCode(Warning::IDENTICAL_SERVER_URLS));
    }


    #[Test]
    #[TestDox('The PathItem object will already warn about its own duplicates')]
    public function itWillNotWarnForDuplicatePathLevelServers(): void
    {
        $identifier = new Identifier('test');
        $server = new Server($identifier, PartialHelper::createServer());
        $sut = new Operation(
            $identifier,
            [$server, $server],
            [],
            Method::GET,
            PartialHelper::createOperation(),
        );

        self::assertEmpty($sut
            ->getWarnings()
            ->findByWarningCode(Warning::IDENTICAL_SERVER_URLS));
    }

    /**
     * @param Parameter[] $pathParameters
     * @param Partial\Parameter[] $operationParameters
     */
    #[Test, DataProvider('provideSimilarParameters')]
    #[TestDox('It warns that similarly named parameters may be confusing')]
    public function itWarnsAgainstSimilarParameters(
        array $pathParameters,
        array $operationParameters,
    ): void {
        $sut = new Operation(
            new Identifier('test'),
            [],
            $pathParameters,
            Method::GET,
            PartialHelper::createOperation(parameters: $operationParameters),
        );
        self::assertNotEmpty($sut
            ->getWarnings()
            ->findByWarningCode(Warning::SIMILAR_NAMES));
    }

    #[Test, TestDox('The PathItem will already warn about its own parameters')]
    public function itWillNotWarnAgainstSimilarPathParameters(): void
    {
        $parameter = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter()
        );

        $sut = new Operation(
            new Identifier('test'),
            [],
            [$parameter, $parameter],
            Method::GET,
            PartialHelper::createOperation(),
        );

        self::assertEmpty($sut->getWarnings()->findByWarningCode(Warning::SIMILAR_NAMES));
    }

    public static function provideParameters(): Generator
    {
        $parentIdentifier = new Identifier('/path');
        $operationId = 'test-operation';
        $method = Method::GET;
        $identifier = $parentIdentifier->append("$operationId($method->value)");

        $case = fn($expected, $pathParameters, $operationParameters) => [
            $parentIdentifier,
            $expected,
            array_map(fn($p) => new Parameter($parentIdentifier, $p), $pathParameters),
            $method,
            PartialHelper::createOperation(
                operationId: $operationId,
                parameters: $operationParameters
            )
        ];

        $unique1 = PartialHelper::createParameter(name: 'unique-name-1');
        $unique2 = PartialHelper::createParameter(name: 'unique-name-2');

        $sameNamePath = PartialHelper::createParameter(name: 'same-name', in: 'path');
        $sameNameQuery = PartialHelper::createParameter(name: 'same-name', in: 'query');


        yield 'one operation parameter' => $case(
            [
                new Parameter($identifier, $unique1),
            ],
            [],
            [$unique1]
        );

        yield 'one unique path parameter, one unique operation parameter' => $case(
            [
                new Parameter($identifier, $unique1),
                new Parameter($parentIdentifier, $unique2),
            ],
            [$unique2],
            [$unique1]
        );

        /** This is technically a unique parameter according to OpenAPI 3.0.3 */
        yield 'one path parameter, one operation parameter, same name, different locations' => $case(
            [
                new Parameter($identifier, $sameNamePath),
                new Parameter($parentIdentifier, $sameNameQuery),
            ],
            [$sameNameQuery],
            [$sameNamePath]
        );

        yield 'one identical path parameter' => $case(
            [
                new Parameter($identifier, $sameNamePath),
            ],
            [$sameNamePath],
            [$sameNamePath]
        );
    }

    public static function provideOperationsToValidate(): Generator
    {
        $parentIdentifier = new Identifier('test');
        $operationId = 'test-id';
        $method = Method::GET;
        $identifier = $parentIdentifier->append($operationId, $method->value);

        $case = fn($expected, $data) => [
            $expected,
            $parentIdentifier,
            $method,
            PartialHelper::createOperation(...array_merge(
                ['operationId' => $operationId],
                $data
            ))
        ];

        yield 'duplicate parameters' => $case(
            InvalidOpenAPI::duplicateParameters(
                $identifier,
                $identifier->append('duplicate', 'path'),
                $identifier->append('duplicate', 'path'),
            ),
            [
                'parameters' => array_pad([], 2, PartialHelper::createParameter(
                    name: 'duplicate',
                    in: 'path',
                ))
            ]
        );
    }

    public static function provideServers(): Generator
    {
        $parentIdentifier = new Identifier('test-api');
        $operationId = 'test-operation';
        $method = Method::GET;
        $identifier = $parentIdentifier->append($operationId, $method->value);

        $pathServers = [
            new Server($parentIdentifier, PartialHelper::createServer(url: '/'))
        ];

        $case = fn($operationServers) => [
            empty($operationServers) ? $pathServers :
                array_map(fn($s) => new Server($identifier, $s), $operationServers),
            $parentIdentifier,
            $operationId,
            $method,
            $pathServers,
            $operationServers
        ];

        yield 'no Path Item Servers' => $case([]);
        yield 'one Path Item Server' => $case([PartialHelper::createServer()]);
        yield 'three Path Item Servers' => $case([
            PartialHelper::createServer(url: 'https://server-one.io'),
            PartialHelper::createServer(url: 'https://server-two.co.uk'),
            PartialHelper::createServer(url: 'https://server-three.net')
        ]);
    }

    /**
     * @return Generator<array{
     *     0: Warning[],
     *     1: Partial\Operation,
     * }>
     */
    public static function provideDuplicateServers(): Generator
    {
        $expectedWarning = new Warning(
            'Server URLs are not unique',
            Warning::IDENTICAL_SERVER_URLS
        );

        $case = fn($servers) => [
            [$expectedWarning],
            PartialHelper::createOperation(servers: $servers),
        ];

        yield 'Completely identical: "/"' => $case([
            PartialHelper::createServer('/'),
            PartialHelper::createServer('/'),
        ]);

        yield 'Completely identical: "https://www.server.net"' => $case([
            PartialHelper::createServer('https://www.server.net'),
            PartialHelper::createServer('https://www.server.net'),
        ]);

        yield 'Identical IF you ignore trailing forward slashes' => $case([
            PartialHelper::createServer(''),
            PartialHelper::createServer('/'),
        ]);
    }

    public static function provideSimilarParameters(): Generator
    {
        $case = fn(array $pathParamNames, array $operationParamNames) => [
            array_map(
                fn($p) => new Parameter(new Identifier(''), PartialHelper::createParameter(name: $p)),
                $pathParamNames
            ),
            array_map(
                fn($p) => PartialHelper::createParameter(name: $p),
                $operationParamNames
            ),
        ];

        yield 'similar path param names' => $case(['param', 'Param'], []);
        yield 'similar operation param names' =>  $case([], ['param', 'Param']);
        yield 'similar param names' => $case(['param'], ['Param']);
    }
}
