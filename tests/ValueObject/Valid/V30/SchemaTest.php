<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema::class)]
#[CoversClass(Partial\Schema::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Type::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
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


    #[Test, DataProvider('provideSchemasToCheckTypes')]
    public function itKnowsWhatTypeItCanBe(
        bool $expected,
        Type $type,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier(''), $partialSchema);

        self::assertSame($expected, $sut->canBe($type));
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
        $case = fn(Identifier $exceptionId, Partial\Schema $schema) => [
            InvalidOpenAPI::emptyComplexSchema($exceptionId),
            $identifier,
            $schema
        ];

        foreach ($xOfs as $keyword => $xOf) {
            yield "empty $keyword" => $case($identifier, $xOf());

            foreach ($xOfs as $otherKeyWord => $otherXOf) {
                yield "$keyword with empty $otherKeyWord inside" => $case(
                    $identifier->append($keyword, '0'),
                    $xOf($otherXOf()),
                );
            }
        }
    }

    /**
     * @return \Generator<array{
     *     0: bool,
     *     1: Type,
     *     2: Partial\Schema
     * }>
     */
    public static function provideSchemasToCheckTypes(): Generator
    {
        foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $desired) {
            yield "it can always be $desired->value for empty schemas" => [
              true,
              $desired,
              PartialHelper::createSchema(),
            ];

            foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $type) {
                yield "can it be $desired->value? top level type: $type->value" => [
                    $desired === $type,
                    $desired,
                    PartialHelper::createSchema(type: $type->value)
                ];

                yield "can it be $desired->value? allOf MUST be $type->value" => [
                    $desired === $type,
                    $desired,
                    PartialHelper::createSchema(allOf: [
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                yield "can it be $desired->value? anyOf MUST be $type->value" => [
                    $desired === $type,
                    $desired,
                    PartialHelper::createSchema(anyOf: [
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                yield "can it be $desired->value? oneOf MUST be $type->value" => [
                    $desired === $type,
                    $desired,
                    PartialHelper::createSchema(oneOf: [
                        PartialHelper::createSchema(type: $type->value),
                    ])
                ];

                if ($type !== Type::String) {
                    yield "can it be $desired->value? anyOf MAY be $type->value|string" => [
                        in_array($desired->value, [$type->value, 'string'], true),
                        $desired,
                        PartialHelper::createSchema(anyOf: [
                            PartialHelper::createSchema(type: 'string'),
                            PartialHelper::createSchema(type: $type->value),
                        ])
                    ];
                }

                if ($type->value !== Type::Boolean) {
                    yield "can it be $desired->value? oneOf MAY be $type->value|boolean" => [
                        in_array($desired->value, [$type->value, 'boolean'], true),
                        $desired,
                        PartialHelper::createSchema(oneOf: [
                            PartialHelper::createSchema(type: 'boolean'),
                            PartialHelper::createSchema(type: $type->value),
                        ])
                    ];
                }

                if ($type !== Type::Integer) {
                    yield "can it be $desired->value? allOf contains oneOf that may be $type->value|integer" => [
                        in_array($desired->value, [$type->value, 'integer'], true),
                        $desired,
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
}
