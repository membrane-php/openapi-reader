<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
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

#[CoversClass(Schema::class)]
#[CoversClass(Partial\Schema::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Type::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
class SchemaTest extends TestCase
{
    #[Test, DataProvider('provideInvalidComplexSchemas')]
    public function itInvalidatesEmptyComplexSchemas(
        InvalidOpenAPI $expected,
        Identifier $identifier,
        Partial\Schema $partialSchema,
    ): void {
        self::expectExceptionObject($expected);

        new Schema($identifier, $partialSchema);
    }

    #[Test]
    public function itInvalidatesInvalidTypes(): void
    {
        $identifier = new Identifier('');
        $schema = PartialHelper::createSchema(type: 'invalid');



        self::expectExceptionObject(InvalidOpenAPI::invalidType($identifier, 'invalid'));

        new Schema($identifier, $schema);
    }

    /** @param Type[] $typesItCanBe */
    #[Test, DataProvider('provideSchemasToCheckTypes')]
    public function itKnowsIfItCanBeACertainType(
        array $typesItCanBe,
        Type $typeToCheck,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier(''), $partialSchema);

        self::assertSame(
            in_array($typeToCheck, $typesItCanBe, true),
            $sut->canBe($typeToCheck)
        );
    }

    /** @param Type[] $typesItCanBe */
    #[Test, DataProvider('provideSchemasToCheckTypes')]
    public function itKnowsIfItCanOnlyBeACertainType(
        array $typesItCanBe,
        Type $typeToCheck,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier(''), $partialSchema);

        self::assertSame(
            [$typeToCheck] === $typesItCanBe,
            $sut->canOnlyBe($typeToCheck)
        );
    }

    /** @param Type[] $typesItCanBe */
    #[Test, DataProvider('provideSchemasToCheckTypes')]
    public function itKnowsIfItCanOnlyBePrimitive(
        array $typesItCanBe,
        Type $typeToCheck,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier(''), $partialSchema);

        self::assertSame(
            !empty(array_filter($typesItCanBe, fn($t) => !in_array(
                $t,
                [Type::Array, Type::Object]
            ))),
            $sut->canBePrimitive()
        );
    }

    #[Test]
    #[DataProvider('provideSchemasAcceptNoTypes')]
    public function itWarnsAgainstImpossibleSchemas(
        bool $expected,
        Partial\Schema $schema,
    ): void {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertSame(
            $expected,
            $sut->getWarnings()->hasWarningCode(Warning::IMPOSSIBLE_SCHEMA)
        );
    }

    #[Test]
    #[TestDox('It determines the relevant numeric inclusive|exclusive maximum, if there is one')]
    #[DataProvider('provideSchemasWithMax')]
    public function itGetsRelevantMaximum(?Limit $expected, Partial\Schema $schema): void
    {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertEquals($expected, $sut->getRelevantMaximum());
    }

    #[Test]
    #[TestDox('It determines the relevant numeric inclusive|exclusive minimum, if there is one')]
    #[DataProvider('provideSchemasWithMin')]
    public function itGetsRelevantMinimum(?Limit $expected, Partial\Schema $schema): void
    {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertEquals($expected, $sut->getRelevantMinimum());
    }

    /**
     * @param Type[] $expected
     */
    #[Test]
    #[TestDox('It gets the types allowed, in a version agnostic format')]
    #[DataProvider('provideSchemasToGetTypes')]
    public function itGetsTypes(array $expected, Partial\Schema $schema): void
    {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertEqualsCanonicalizing($expected, $sut->getTypes());
    }

    public static function provideInvalidComplexSchemas(): Generator
    {
        $xOfs = [
            'allOf' => fn(Partial\Schema ...$subSchemas) => PartialHelper::createSchema(
                allOf: $subSchemas
            ),
            'anyOf' => fn(Partial\Schema ...$subSchemas) => PartialHelper::createSchema(
                anyOf: $subSchemas
            ),
            'oneOf' => fn(Partial\Schema ...$subSchemas) => PartialHelper::createSchema(
                oneOf: $subSchemas
            ),
        ];

        $identifier = new Identifier('test-schema');
        $case = fn(Identifier $exceptionId, Partial\Schema $schema, string $keyword) => [
            InvalidOpenAPI::mustBeNonEmpty($exceptionId, $keyword),
            $identifier,
            $schema
        ];

        foreach ($xOfs as $keyword => $xOf) {
            yield "empty $keyword" => $case($identifier, $xOf(), $keyword);

            foreach ($xOfs as $otherKeyWord => $otherXOf) {
                yield "$keyword with empty $otherKeyWord inside" => $case(
                    $identifier->append($keyword, '0'),
                    $xOf($otherXOf()),
                    $otherKeyWord,
                );
            }
        }
    }

    /**
     * @return \Generator<array{
     *     0: Type[],
     *     1: Type,
     *     2: Partial\Schema,
     * }>
     */
    public static function provideSchemasToCheckTypes(): Generator
    {
        foreach (Type::cases() as $typeToCheck) {
            yield "$typeToCheck->value? empty schema" => [
              Type::casesForVersion(OpenAPIVersion::Version_3_0),
              $typeToCheck,
              PartialHelper::createSchema(),
            ];

            foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $type) {
                yield "$typeToCheck->value? top level type: $type->value" => [
                    [$type],
                    $typeToCheck,
                    PartialHelper::createSchema(type: $type->value)
                ];

                yield "$typeToCheck->value? allOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    PartialHelper::createSchema(allOf: [
                        PartialHelper::createSchema(type: $type->value),
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? anyOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    PartialHelper::createSchema(anyOf: [
                        PartialHelper::createSchema(type: $type->value),
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? oneOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    PartialHelper::createSchema(oneOf: [
                        PartialHelper::createSchema(type: $type->value),
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? top-level type: string, allOf MUST be $type->value" => [
                    $type === Type::String ? [Type::String] : [],
                    $typeToCheck,
                    PartialHelper::createSchema(
                        type: Type::String->value,
                        allOf: [
                            PartialHelper::createSchema(type: $type->value),
                            PartialHelper::createSchema(type: $type->value),
                        ]
                    )
                ];

                yield "$typeToCheck->value? top-level type: number, anyOf MUST be $type->value" => [
                    $type === Type::Number ? [Type::Number] : [],
                    $typeToCheck,
                    PartialHelper::createSchema(
                        type: Type::Number->value,
                        anyOf: [
                            PartialHelper::createSchema(type: $type->value),
                            PartialHelper::createSchema(type: $type->value),
                        ]
                    )
                ];

                yield "$typeToCheck->value? top-level type: array, oneOf MUST be $type->value" => [
                    $type === Type::Array ? [Type::Array] : [],
                    $typeToCheck,
                    PartialHelper::createSchema(
                        type: Type::Array->value,
                        oneOf: [
                            PartialHelper::createSchema(type: $type->value),
                            PartialHelper::createSchema(type: $type->value),
                        ]
                    )
                ];


                if ($type !== Type::String) {
                    yield "$typeToCheck->value? anyOf MAY be $type->value|string" => [
                        [$type, Type::String],
                        $typeToCheck,
                        PartialHelper::createSchema(anyOf: [
                            PartialHelper::createSchema(type: 'string'),
                            PartialHelper::createSchema(type: $type->value),
                        ])
                    ];
                }

                if ($type !== Type::Boolean) {
                    yield "$typeToCheck->value? oneOf MAY be $type->value|boolean" => [
                        [$type, Type::Boolean],
                        $typeToCheck,
                        PartialHelper::createSchema(oneOf: [
                            PartialHelper::createSchema(type: 'boolean'),
                            PartialHelper::createSchema(type: $type->value),
                        ])
                    ];
                }

                if ($type !== Type::Integer) {
                    yield "can it be $typeToCheck->value? allOf contains oneOf that may be $type->value|integer" => [
                        [$type, Type::Integer],
                        $typeToCheck,
                        PartialHelper::createSchema(allOf: [
                            PartialHelper::createSchema(oneOf: [
                                PartialHelper::createSchema(type: $type->value),
                                PartialHelper::createSchema(type: 'integer')
                            ]),
                        ])
                    ];
                }
            }
        }
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Schema
     * }>
     */
    public static function provideSchemasAcceptNoTypes(): Generator
    {
        foreach (self::provideSchemasToCheckTypes() as $case => $dataSet) {
            yield $case => [
              empty($dataSet[0]),
              $dataSet[2]
            ];
        }
    }

    /**
     * @return Generator<array{
     *     0: ?Limit,
     *     1: Partial\Schema
     * }>
     */
    public static function provideSchemasWithMax(): Generator
    {
        yield 'no min or max' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: null,
            ),
        ];

        yield 'inclusive min' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'exclusive min' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'inclusive max' => [
            new Limit(1, false),
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'exclusive max' => [
            new Limit(1, true),
            PartialHelper::createSchema(
                exclusiveMaximum: true,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'inclusive max and exclusive min' => [
            new Limit(5, false),
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: 5,
                minimum: 1,
            ),
        ];
    }

    /**
     * @return Generator<array{
     *     0: ?Limit,
     *     1: Partial\Schema
     * }>
     */
    public static function provideSchemasWithMin(): Generator
    {
        yield 'no min or max' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: null,
            ),
        ];

        yield 'inclusive min' => [
            new Limit(1, false),
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'exclusive min' => [
            new Limit(1, true),
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'inclusive max' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'exclusive max' => [
            null,
            PartialHelper::createSchema(
                exclusiveMaximum: true,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'inclusive max and exclusive min' => [
            new Limit(1, true),
            PartialHelper::createSchema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: 5,
                minimum: 1,
            ),
        ];
    }

    /**
     * @return \Generator<array{ 0:Type[], 1:Partial\Schema }>
     */
    public static function provideSchemasToGetTypes(): Generator
    {
        yield 'no type' => [
            Type::casesForVersion(OpenAPIVersion::Version_3_0),
            PartialHelper::createSchema(),
        ];

        yield 'nullable' => [
            [Type::Null, ...Type::casesForVersion(OpenAPIVersion::Version_3_0)],
            PartialHelper::createSchema(nullable: true)
        ];

        yield 'string' => [[Type::String], PartialHelper::createSchema(type: 'string')];
        yield 'integer' => [[Type::Integer], PartialHelper::createSchema(type: 'integer')];
        yield 'number' => [[Type::Number], PartialHelper::createSchema(type: 'number')];
        yield 'boolean' => [[Type::Boolean], PartialHelper::createSchema(type: 'boolean')];
        yield 'array' => [
            [Type::Array],
            PartialHelper::createSchema(type: 'array', items: PartialHelper::createSchema()),
        ];
        yield 'object' => [[Type::Object], PartialHelper::createSchema(type: 'object')];

        yield 'nullable string' => [
            [Type::String, Type::Null],
            PartialHelper::createSchema(type: 'string', nullable: true),
        ];
        yield 'nullable integer' => [
            [Type::Integer, Type::Null],
            PartialHelper::createSchema(type: 'integer', nullable: true),
        ];
        yield 'nullable number' => [
            [Type::Number, Type::Null],
            PartialHelper::createSchema(type: 'number', nullable: true),
        ];
        yield 'nullable boolean' => [
            [Type::Boolean, Type::Null],
            PartialHelper::createSchema(type: 'boolean', nullable: true),
        ];
        yield 'nullable array' => [
            [Type::Array, Type::Null],
            PartialHelper::createSchema(type: 'array', nullable: true, items: PartialHelper::createSchema()),
        ];
        yield 'nullable object' => [
            [Type::Object, Type::Null],
            PartialHelper::createSchema(type: 'object', nullable: true),
        ];
    }
}
