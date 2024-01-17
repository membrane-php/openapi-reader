<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Operation::class)]
#[CoversClass(Partial\Operation::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[CoversClass(CannotSupport::class)]
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

        new Operation(new Identifier(''), [], Method::GET, $partialOperation);
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
        $sut = new Operation($parentIdentifier, $pathParameters, $method, $partialOperation);

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

        new Operation($parentIdentifier, [], $method, $partialOperation);
    }

    #[Test, DataProvider('provideOperationsToValidate')]
    public function itValidatesOperations(
        InvalidOpenAPI $expected,
        Identifier $parentIdentifier,
        Method $method,
        Partial\Operation $partialOperation,
    ): void {
        self::expectExceptionObject($expected);

        new Operation($parentIdentifier, [], $method, $partialOperation);
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
}
