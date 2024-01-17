<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
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
#[UsesClass(Identifier::class)]
#[UsesClass(Operation::class)]
#[UsesClass(Partial\Operation::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Partial\Parameter::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Partial\Schema::class)]
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
        $expected = array_map(fn($p) => new Parameter($identifier, $p), $partialExpected);

        $sut = new PathItem($identifier, $partialPathItem);

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

        new PathItem($identifier, $pathItem);
    }

    #[Test, DataProvider('provideSimilarNames')]
    #[TestDox('It warns that similar names, though valid, may be confusing')]
    public function itWarnsAgainstDuplicateNames(string $name1, string $name2): void
    {
        $sut = new PathItem(
            new Identifier('test-path-item'),
            PartialHelper::createPathItem(parameters: [
                PartialHelper::createParameter(name: $name1, in:'path'),
                PartialHelper::createParameter(name: $name2, in:'query')
            ])
        );

        self::assertSame(
            Warning::SIMILAR_NAMES,
            $sut->getWarnings()->all()[0]->code
        );
    }

    #[Test, DataProvider('provideRedundantMethods')]
    #[TestDox('it warns that options, head and trace are redundant methods for an OpenAPI')]
    public function itWarnsAgainstRedundantMethods(string $method): void
    {
        $operations = [$method => PartialHelper::createOperation()];

        $partialPathItem = PartialHelper::createPathItem(...$operations);

        $sut = new PathItem(new Identifier('test'), $partialPathItem);

        self::assertEquals(
            new Warning(
                "$method is redundant in an OpenAPI Specification.",
                Warning::REDUNDANT_METHOD
            ),
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
            PartialHelper::createPathItem(
                ...$operations
            )
        );

        self::assertEquals($expected, $sut->getOperations());
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
            PartialHelper::createPathItem(parameters: [
                $p1
            ]),
        ];

        yield 'three parameters' => [
            [$p1, $p2, $p3],
            PartialHelper::createPathItem(parameters: [$p1, $p2, $p3]),
        ];
    }

    public static function provideSimilarNames(): Generator
    {
        yield 'two identical names' => ['param', 'param'];
        yield 'two names that only differ in case' => ['param', 'PARAM'];
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
}
