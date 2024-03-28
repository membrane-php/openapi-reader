<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
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

    /**
     * @param string[] $types
     */
    #[Test, DataProvider('provideSchemasToCheckTypes')]
    public function itKnowsWhatTypeItCanBe(
        bool $expected,
        array $types,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier(''), $partialSchema);

        self::assertSame($expected, $sut->canItBeThisType(...$types));
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
     *     1: string[],
     *     2: Partial\Schema
     * }>
     */
    public static function provideSchemasToCheckTypes(): Generator
    {
        $types = ['boolean', 'number', 'integer', 'string', 'array', 'object'];

        foreach ($types as $desired) {
            yield "it can always be $desired for empty schemas" => [
              true,
              [$desired],
              PartialHelper::createSchema(),
            ];

            foreach ($types as $type) {
                yield "can it be $desired? top level type: $type" => [
                    $desired === $type,
                    [$desired],
                    PartialHelper::createSchema(type: $type)
                ];

                yield "can it be $desired? allOf MUST be $type" => [
                    $desired === $type,
                    [$desired],
                    PartialHelper::createSchema(allOf: [
                        PartialHelper::createSchema(type: $type),
                    ])
                ];

                yield "can it be $desired? anyOf MUST be $type" => [
                    $desired === $type,
                    [$desired],
                    PartialHelper::createSchema(anyOf: [
                        PartialHelper::createSchema(type: $type),
                    ])
                ];

                yield "can it be $desired? oneOf MUST be $type" => [
                    $desired === $type,
                    [$desired],
                    PartialHelper::createSchema(oneOf: [
                        PartialHelper::createSchema(type: $type),
                    ])
                ];

                if ($type !== 'string') {
                    yield "can it be $desired? anyOf MAY be $type|string" => [
                        in_array($desired, [$type, 'string'], true),
                        [$desired],
                        PartialHelper::createSchema(anyOf: [
                            PartialHelper::createSchema(type: 'string'),
                            PartialHelper::createSchema(type: $type),
                        ])
                    ];
                }

                if ($type !== 'boolean') {
                    yield "can it be $desired? oneOf MAY be $type|boolean" => [
                        in_array($desired, [$type, 'boolean'], true),
                        [$desired],
                        PartialHelper::createSchema(oneOf: [
                            PartialHelper::createSchema(type: $type),
                            PartialHelper::createSchema(type: 'boolean'),
                        ])
                    ];
                }

                if ($type !== 'integer') {
                    yield "can it be $desired? allOf contains oneOf that may be $type|integer" => [
                        in_array($desired, [$type, 'integer'], true),
                        [$desired],
                        PartialHelper::createSchema(allOf: [
                            PartialHelper::createSchema(oneOf: [
                                PartialHelper::createSchema(type: $type),
                                PartialHelper::createSchema(type: 'integer')
                            ]),
                        ])
                    ];
                }
            }
        }
    }
}
