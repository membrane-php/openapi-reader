<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
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

#[CoversClass(PathItem::class)]
#[CoversClass(Partial\PathItem::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Server::class)]
#[UsesClass(Partial\Server::class)]
#[UsesClass(Operation::class)]
#[UsesClass(Partial\Operation::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Partial\Parameter::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
#[UsesClass(Method::class)]
class PathItemTest extends TestCase
{
    /**
     * @param Partial\Parameter[] $partialExpected
     * @param Partial\PathItem $partialPathItem
     */
    #[Test, DataProvider('providePartialPathItems')]
    public function itValidatesParameters(
        array $partialExpected,
        Partial\PathItem $partialPathItem
    ): void {
        $identifier = new Identifier('test-path-item');
        $expected = array_values(array_map(
            fn($p) => new Parameter($identifier, $p),
            $partialExpected
        ));

        $sut = new PathItem($identifier, [], $partialPathItem);

        self::assertEquals($expected, $sut->parameters);
    }

    #[Test]
    #[TestDox('It invalidates Parameters with identical "name" & "in" values')]
    public function itInvalidatesDuplicateParameters(): void
    {
        $identifier = new Identifier('test-path-item');

        $name = 'param';
        $in = 'path';
        $param = PartialHelper::createParameter(name: $name, in: $in);
        $paramIdentifier = $identifier->append($name, $in);

        $pathItem = PartialHelper::createPathItem(parameters: [$param, $param]);

        self::expectExceptionObject(InvalidOpenAPI::duplicateParameters(
            $identifier,
            $paramIdentifier,
            $paramIdentifier,
        ));

        new PathItem($identifier, [], $pathItem);
    }

    #[Test]
    #[TestDox('Identical names in different locations may serve a purpose')]
    public function itDoesNotWarnAgainstIdenticalNames(): void
    {
        $sut = new PathItem(
            new Identifier('test-path-item'),
            [],
            PartialHelper::createPathItem(parameters: [
                PartialHelper::createParameter(name: 'param', in: 'path'),
                PartialHelper::createParameter(name: 'param', in: 'query')
            ])
        );

        self::assertEmpty($sut
            ->getWarnings()
            ->findByWarningCode(Warning::SIMILAR_NAMES));
    }

    #[Test]
    #[TestDox('It warns that similar names, though valid, may be confusing')]
    public function itWarnsAgainstSimilarNames(): void
    {
        $sut = new PathItem(
            new Identifier('test-path-item'),
            [],
            PartialHelper::createPathItem(parameters: [
                PartialHelper::createParameter(name: 'param'),
                PartialHelper::createParameter(name: 'PARAM')
            ])
        );

        self::assertNotEmpty($sut
            ->getWarnings()
            ->findByWarningCode(Warning::SIMILAR_NAMES));
    }

    /**
     * @param Warning[] $expected
     */
    #[Test, DataProvider('provideDuplicateServers')]
    public function itWarnsAgainstDuplicateServers(
        array $expected,
        Partial\PathItem $pathItem,
    ): void {
        $sut = new PathItem(new Identifier('test'), [], $pathItem);

        self::assertEquals($expected, $sut
            ->getWarnings()
            ->findByWarningCode(Warning::IDENTICAL_SERVER_URLS));
    }


    #[Test]
    #[TestDox('The OpenAPI object will already warn about its own duplicates')]
    public function itWillNotWarnForDuplicateRootLevelServers(): void
    {
        $identifier = new Identifier('test');
        $server = new Server($identifier, PartialHelper::createServer());
        $sut = new PathItem(
            $identifier,
            [$server, $server],
            PartialHelper::createPathItem()
        );

        self::assertEmpty($sut
            ->getWarnings()
            ->findByWarningCode(Warning::IDENTICAL_SERVER_URLS));
    }

    #[Test, DataProvider('provideRedundantMethods')]
    #[TestDox('it warns that options, head and trace are redundant methods for an OpenAPI')]
    public function itWarnsAgainstRedundantMethods(string $method): void
    {
        $operations = [$method => PartialHelper::createOperation()];

        $partialPathItem = PartialHelper::createPathItem(...$operations);

        $sut = new PathItem(new Identifier('test'), [], $partialPathItem);

        self::assertEquals(
            new Warning(
                "$method is redundant in an OpenAPI Specification.",
                Warning::REDUNDANT_METHOD
            ),
            $sut->getWarnings()->all()[0]
        );
    }

    #[Test]
    #[TestDox('it warns that there are no operations specified on this path')]
    public function itWarnsAgainstHavingNoOperations(): void
    {
        $partialPathItem = PartialHelper::createPathItem();

        $sut = new PathItem(new Identifier('test'), [], $partialPathItem);

        self::assertEquals(
            new Warning('No Operations on Path', Warning::EMPTY_PATH),
            $sut->getWarnings()->all()[0]
        );
    }

    /**
     * @param array<string,Operation> $expected
     * @param Partial\Operation[] $operations
     */
    #[Test, DataProvider('provideOperationsToGet')]
    #[TestDox('it has a convenience method that gets all operations mapped by their method')]
    public function itCanGetAllOperations(
        array $expected,
        Identifier $identifier,
        array $operations,
    ): void {
        $sut = new PathItem(
            $identifier,
            [],
            PartialHelper::createPathItem(
                ...$operations
            )
        );

        self::assertEquals($expected, $sut->getOperations());
    }

    /**
     * @param array<int,Partial\Server> $expected
     * @param array<int,Server> $openapiServers
     * @param Partial\Server[] $pathItemServers
     */
    #[Test, DataProvider('provideServers')]
    #[TestDox('If a Path Item specifies any Servers, then it should override any OpenAPI servers')]
    public function itOverridesOpenAPILevelServers(
        array $expected,
        Identifier $identifier,
        array $openapiServers,
        array $pathItemServers,
    ): void {
        $sut = new PathItem(
            $identifier,
            $openapiServers,
            PartialHelper::createPathItem(
                servers: $pathItemServers
            )
        );

        self::assertEquals(array_values($expected), $sut->servers);
    }

    public static function providePartialPathItems(): Generator
    {
        $p1 = PartialHelper::createParameter(name: 'p1');
        $p2 = PartialHelper::createParameter(name: 'p2');
        $p3 = PartialHelper::createParameter(name: 'p3');

        yield 'no parameters' => [
            [],
            PartialHelper::createPathItem(parameters: []),
        ];

        yield 'one parameter' => [
            [$p1],
            PartialHelper::createPathItem(parameters: [$p1]),
        ];

        yield 'one parameter with name used as key' => [
            [$p1],
            PartialHelper::createPathItem(parameters: ['p1' => $p1]),
        ];

        yield 'three parameters' => [
            [$p1, $p2, $p3],
            PartialHelper::createPathItem(parameters: [$p1, $p2, $p3]),
        ];

        yield 'three parameters with names used as keys' => [
            [$p1, $p2, $p3],
            PartialHelper::createPathItem(parameters: [
                'p1' => $p1,
                'p2' => $p2,
                'p3' => $p3,
            ]),
        ];
    }

    public static function provideSimilarNames(): Generator
    {
        yield 'two names that only differ in case' => ['param', 'PARAM'];

    }

    /**
     * @return Generator<array{
     *     0: Warning[],
     *     1: Partial\PathItem,
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
            PartialHelper::createPathItem(servers: $servers),
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

    public static function provideRedundantMethods(): Generator
    {
        yield 'options' => ['options'];
        yield 'trace' => ['trace'];
        yield 'head' => ['head'];
    }

    public static function provideOperationsToGet(): Generator
    {
        $identifier = new Identifier('test');
        $case = fn($expected, $operations) => [
            $expected,
            $identifier,
            $operations,
        ];

        yield 'no operations' => $case([], []);

        $partialOperation = fn($method) => PartialHelper::createOperation(
            operationId: "$method-id"
        );

        $validOperation = fn($method) => new Operation(
            $identifier,
            [],
            [],
            Method::from($method),
            $partialOperation($method),
        );

        yield '"get" operation' => $case(
            [
                'get' => $validOperation('get')
            ],
            ['get' => $partialOperation('get')]
        );

        yield 'every operation' => $case(
            [
                'get' => $validOperation('get'),
                'put' => $validOperation('put'),
                'post' => $validOperation('post'),
                'delete' => $validOperation('delete'),
                'options' => $validOperation('options'),
                'head' => $validOperation('head'),
                'patch' => $validOperation('patch'),
                'trace' => $validOperation('trace'),
            ],
            [
                'get' => $partialOperation('get'),
                'put' => $partialOperation('put'),
                'post' => $partialOperation('post'),
                'delete' => $partialOperation('delete'),
                'options' => $partialOperation('options'),
                'head' => $partialOperation('head'),
                'patch' => $partialOperation('patch'),
                'trace' => $partialOperation('trace'),
            ]
        );
    }

    public static function provideServers(): Generator
    {
        $parentIdentifier = new Identifier('test-api');
        $identifier = $parentIdentifier->append('test-path');

        $openAPIServers = [
            new Server($parentIdentifier, PartialHelper::createServer(url: '/')),
            new Server($parentIdentifier, PartialHelper::createServer(url: '/petstore.io')),
        ];

        $case = fn($pathServers) => [
            empty($pathServers) ? $openAPIServers :
                array_map(fn($s) => new Server($identifier, $s), $pathServers),
            $identifier,
            $openAPIServers,
            $pathServers
        ];

        yield 'no Path Item Servers' => $case([]);
        yield 'one Path Item Server with its url used as a key' => $case([
            'https://server-one.io' =>
                PartialHelper::createServer(url: 'https://server-one.io')
        ]);
        yield 'three Path Item Servers' => $case([
            'https://server-one.io' =>
                PartialHelper::createServer(url: 'https://server-one.io'),
            'https://server-two.co.uk' =>
                PartialHelper::createServer(url: 'https://server-two.co.uk'),
            'https://server-three.net' =>
                PartialHelper::createServer(url: 'https://server-three.net')
        ]);
    }

    public static function provideServersToWarnAgainst(): Generator
    {

    }
}
