<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Tests\Fixtures\ProvidesReviewedSchemas;
use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
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
    #[Test]
    #[DataProviderExternal(ProvidesReviewedSchemas::class, 'provideV3xReviews')]
    #[DataProviderExternal(ProvidesReviewedSchemas::class, 'provideV30Reviews')]
    public function itReviewsSchema(Partial\Schema $schema, array $warnings): void
    {
        $sut = new Schema(new Identifier('test'), $schema);

        self::assertEqualsCanonicalizing($warnings, $sut->getWarnings()->all());
    }

    #[Test]
    #[DataProviderExternal(ProvidesReviewedSchemas::class, 'provideV3xReviews')]
    #[DataProviderExternal(ProvidesReviewedSchemas::class, 'provideV30Reviews')]
    public function itSimplifiesSchema(
        Partial\Schema $schema,
        $_,
        string $propertyName,
        mixed $expected,
    ): void {
        $sut = new Schema(new Identifier('test'), $schema);

        self::assertEquals($expected, $sut->value->{$propertyName});
    }

    #[Test]
    #[DataProvider('provideInvalidSchemas')]
    public function itValidatesSchema(
        InvalidOpenAPI $expected,
        Identifier $identifier,
        Partial\Schema $partialSchema,
    ): void {
        self::expectExceptionObject($expected);

        new Schema($identifier, $partialSchema);
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

        self::assertEquals($expected, $sut->value->maximum);
    }

    #[Test]
    #[TestDox('It determines the relevant numeric inclusive|exclusive minimum, if there is one')]
    #[DataProvider('provideSchemasWithMin')]
    public function itGetsRelevantMinimum(?Limit $expected, Partial\Schema $schema): void
    {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertEquals($expected, $sut->value->minimum);
    }

    /**
     * @param Type[] $expected
     */
    #[Test]
    #[TestDox('It gets types specified, in a version agnostic format')]
    #[DataProvider('provideSchemasToGetTypes')]
    public function itGetsTypes(array $expected, Partial\Schema $schema): void
    {
        $sut = new Schema(new Identifier(''), $schema);

        self::assertEqualsCanonicalizing($expected, $sut->value->types);
    }

    public static function provideInvalidSchemas(): Generator
    {
        yield 'invalid type' => [
            InvalidOpenAPI::invalidType(new Identifier('invalid type'), 'invalid'),
            new Identifier('invalid type'),
            new Partial\Schema(type: 'invalid'),
        ];

        yield 'properties list' => [
            InvalidOpenAPI::mustHaveStringKeys(
                new Identifier('properties list'),
                'properties',
            ),
            new Identifier('properties list'),
            new Partial\Schema(properties: [new Partial\Schema()]),
        ];

        yield 'negative maxLength' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger(
                new Identifier('negative maxLength'),
                'maxLength',
            ),
            new Identifier('negative maxLength'),
            new Partial\Schema(maxLength: -1),
        ];

        yield 'negative maxItems' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger(
                new Identifier('negative maxItems'),
                'maxItems',
            ),
            new Identifier('negative maxItems'),
            new Partial\Schema(maxItems: -1),
        ];

        yield 'negative maxProperties' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger(
                new Identifier('negative maxProperties'),
                'maxProperties',
            ),
            new Identifier('negative maxProperties'),
            new Partial\Schema(maxProperties: -1),
        ];

        yield 'zero multipleOf' => [
            InvalidOpenAPI::keywordCannotBeZero(
                new Identifier('zero multipleOf'),
                'multipleOf',
            ),
            new Identifier('zero multipleOf'),
            new Partial\Schema(multipleOf: 0),
        ];

        yield 'default does not conform to type' => [
            InvalidOpenAPI::defaultMustConformToType(
                new Identifier('non-conforming default'),
            ),
            new Identifier('non-conforming default'),
            new Partial\Schema(type: 'string', default: new Value(1)),
        ];

        yield 'numeric exclusiveMaximum in 3.0' => [
            InvalidOpenAPI::numericExclusiveMinMaxIn30(
                new Identifier('numeric exclusiveMaximum'),
                'exclusiveMaximum'
            ),
            new Identifier('numeric exclusiveMaximum'),
            new Partial\Schema(exclusiveMaximum: 5),
        ];

        yield 'numeric exclusiveMinimum in 3.0' => [
            InvalidOpenAPI::numericExclusiveMinMaxIn30(
                new Identifier('numeric exclusiveMinimum'),
                'exclusiveMinimum'
            ),
            new Identifier('numeric exclusiveMinimum'),
            new Partial\Schema(exclusiveMinimum: 5),
        ];
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
                Type::cases(),
                $typeToCheck,
                new Partial\Schema(),
            ];

            foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $type) {
                yield "$typeToCheck->value? top level type: $type->value" => [
                    [$type],
                    $typeToCheck,
                    new Partial\Schema(type: $type->value)
                ];

                yield "$typeToCheck->value? allOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    new Partial\Schema(allOf: [
                        new Partial\Schema(type: $type->value),
                        new Partial\Schema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? anyOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    new Partial\Schema(anyOf: [
                        new Partial\Schema(type: $type->value),
                        new Partial\Schema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? oneOf MUST be $type->value" => [
                    [$type],
                    $typeToCheck,
                    new Partial\Schema(oneOf: [
                        new Partial\Schema(type: $type->value),
                        new Partial\Schema(type: $type->value),
                    ])
                ];

                yield "$typeToCheck->value? top-level type: string, allOf MUST be $type->value" => [
                    $type === Type::String ? [Type::String] : [],
                    $typeToCheck,
                    new Partial\Schema(
                        type: Type::String->value,
                        allOf: [
                            new Partial\Schema(type: $type->value),
                            new Partial\Schema(type: $type->value),
                        ]
                    )
                ];

                yield "$typeToCheck->value? top-level type: number, anyOf MUST be $type->value" => [
                    $type === Type::Number ? [Type::Number] : [],
                    $typeToCheck,
                    new Partial\Schema(
                        type: Type::Number->value,
                        anyOf: [
                            new Partial\Schema(type: $type->value),
                            new Partial\Schema(type: $type->value),
                        ]
                    )
                ];

                yield "$typeToCheck->value? top-level type: array, oneOf MUST be $type->value" => [
                    $type === Type::Array ? [Type::Array] : [],
                    $typeToCheck,
                    new Partial\Schema(
                        type: Type::Array->value,
                        oneOf: [
                            new Partial\Schema(type: $type->value),
                            new Partial\Schema(type: $type->value),
                        ]
                    )
                ];


                if ($type !== Type::String) {
                    yield "$typeToCheck->value? anyOf MAY be $type->value|string" => [
                        [$type, Type::String],
                        $typeToCheck,
                        new Partial\Schema(anyOf: [
                            new Partial\Schema(type: 'string'),
                            new Partial\Schema(type: $type->value),
                        ])
                    ];
                }

                if ($type !== Type::Boolean) {
                    yield "$typeToCheck->value? oneOf MAY be $type->value|boolean" => [
                        [$type, Type::Boolean],
                        $typeToCheck,
                        new Partial\Schema(oneOf: [
                            new Partial\Schema(type: 'boolean'),
                            new Partial\Schema(type: $type->value),
                        ])
                    ];
                }

                if ($type !== Type::Integer) {
                    yield "can it be $typeToCheck->value? allOf contains oneOf that may be $type->value|integer" => [
                        [$type, Type::Integer],
                        $typeToCheck,
                        new Partial\Schema(allOf: [
                            new Partial\Schema(oneOf: [
                                new Partial\Schema(type: $type->value),
                                new Partial\Schema(type: 'integer')
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
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: null,
            ),
        ];

        yield 'inclusive min' => [
            null,
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'exclusive min' => [
            null,
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'inclusive max' => [
            new Limit(1, false),
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'exclusive max' => [
            new Limit(1, true),
            new Partial\Schema(
                exclusiveMaximum: true,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'inclusive max and exclusive min' => [
            new Limit(5, false),
            new Partial\Schema(
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
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: null,
            ),
        ];

        yield 'inclusive min' => [
            new Limit(1, false),
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'exclusive min' => [
            new Limit(1, true),
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: true,
                maximum: null,
                minimum: 1,
            ),
        ];

        yield 'inclusive max' => [
            null,
            new Partial\Schema(
                exclusiveMaximum: false,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'exclusive max' => [
            null,
            new Partial\Schema(
                exclusiveMaximum: true,
                exclusiveMinimum: false,
                maximum: 1,
                minimum: null,
            ),
        ];

        yield 'inclusive max and exclusive min' => [
            new Limit(1, true),
            new Partial\Schema(
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
            [],
            new Partial\Schema(),
        ];

        yield 'nullable' => [
            [],
            new Partial\Schema(nullable: true)
        ];

        yield 'string' => [[Type::String], new Partial\Schema(type: 'string')];
        yield 'integer' => [[Type::Integer], new Partial\Schema(type: 'integer')];
        yield 'number' => [[Type::Number], new Partial\Schema(type: 'number')];
        yield 'boolean' => [[Type::Boolean], new Partial\Schema(type: 'boolean')];
        yield 'array' => [
            [Type::Array],
            new Partial\Schema(type: 'array', items: new Partial\Schema()),
        ];
        yield 'object' => [[Type::Object], new Partial\Schema(type: 'object')];

        yield 'nullable string' => [
            [Type::String, Type::Null],
            new Partial\Schema(type: 'string', nullable: true),
        ];
        yield 'nullable integer' => [
            [Type::Integer, Type::Null],
            new Partial\Schema(type: 'integer', nullable: true),
        ];
        yield 'nullable number' => [
            [Type::Number, Type::Null],
            new Partial\Schema(type: 'number', nullable: true),
        ];
        yield 'nullable boolean' => [
            [Type::Boolean, Type::Null],
            new Partial\Schema(type: 'boolean', nullable: true),
        ];
        yield 'nullable array' => [
            [Type::Array, Type::Null],
            new Partial\Schema(type: 'array', nullable: true, items: new Partial\Schema()),
        ];
        yield 'nullable object' => [
            [Type::Object, Type::Null],
            new Partial\Schema(type: 'object', nullable: true),
        ];
    }
}
