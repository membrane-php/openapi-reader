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

    #[Test, DataProvider('provideSchemasToCheckIfTheyCanBeAnObject')]
    public function itKnowsIfItCanBeAnObject(
        bool $expected,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier('sut'), $partialSchema);

        self::assertSame($expected, $sut->canItBeAnObject());
    }

    #[Test, DataProvider('provideSchemasToCheckIfTheyCanBeAnArray')]
    public function itKnowsIfItCanBeAnArray(
        bool $expected,
        Partial\Schema $partialSchema,
    ): void {
        $sut = new Schema(new Identifier('sut'), $partialSchema);

        self::assertSame($expected, $sut->canItBeAnArray());
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

    public static function provideSchemasToCheckIfTheyCanBeAnObject(): Generator
    {
        return self::provideSchemasToCheckIfTheyCanBeAType('object');
    }

    public static function provideSchemasToCheckIfTheyCanBeAnArray(): Generator
    {
        return self::provideSchemasToCheckIfTheyCanBeAType('array');
    }

    private static function provideSchemasToCheckIfTheyCanBeAType(string $desiredType): Generator
    {
        $types = ['boolean', 'number', 'integer', 'string', 'array', 'object'];

        foreach ($types as $type) {
            yield "top-level type:$type" => [
                $type === $desiredType,
                PartialHelper::createSchema(type: $type)
            ];

            yield "no top-level type, allOf MUST be $type" => [
                $type === $desiredType,
                PartialHelper::createSchema(allOf: [
                    PartialHelper::createSchema(type: $type),
                ])
            ];

            yield "no top-level type, anyOf MUST be $type" => [
                $type === $desiredType,
                PartialHelper::createSchema(anyOf: [
                    PartialHelper::createSchema(type: $type),
                ])
            ];

            yield "no top-level type, oneOf MUST be $type" => [
                $type === $desiredType,
                PartialHelper::createSchema(oneOf: [
                    PartialHelper::createSchema(type: $type),
                ])
            ];

            yield "no top-level type, anyOf MAY be $type or string" => [
                $desiredType === $type,
                PartialHelper::createSchema(anyOf: [
                    PartialHelper::createSchema(type: $type),
                    PartialHelper::createSchema(type: 'string')
                ])
            ];

            yield "no top-level type, oneOf MAY be $type or boolean" => [
                $desiredType === $type,
                PartialHelper::createSchema(oneOf: [
                    PartialHelper::createSchema(type: $type),
                    PartialHelper::createSchema(type: 'boolean')
                ])
            ];

            yield "no top-level type, allOf contains oneOf that may be $type or integer" => [
                $desiredType === $type,
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
