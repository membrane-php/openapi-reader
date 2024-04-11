<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\Enum;

use Generator;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type::class)]
class TypeTest extends TestCase
{

    /** @param Type[] $expected */
    #[Test, DataProvider('provideCasesForVersion')]
    public function itGetsCasesForVersion(
        array $expected,
        OpenAPIVersion $version
    ): void {
        self::assertSame($expected, Type::casesForVersion($version));
    }

    /** @param string[] $expected */
    #[Test, DataProvider('provideValuesForVersion')]
    public function itGetsValuesForVersion(
        array $expected,
        OpenAPIVersion $version
    ): void {
        self::assertSame($expected, Type::valuesForVersion($version));
    }

    #[Test, DataProvider('provideStringsToTryFromVersion')]
    public function itTriesFromVersion(
        ?Type $expected,
        OpenAPIVersion $version,
        string $type
    ): void {
        self::assertSame($expected, Type::tryFromVersion($version, $type));
    }

    /** @return Generator<array{ 0: Type[], 1: OpenAPIVersion }> */
    public static function provideCasesForVersion(): Generator
    {
        yield '3.0' => [
            [
                Type::Boolean,
                Type::Number,
                Type::Integer,
                Type::String,
                Type::Array,
                Type::Object,
            ],
            OpenAPIVersion::Version_3_0
        ];

        yield '3.1' => [
            [
                Type::Null,
                Type::Boolean,
                Type::Number,
                Type::Integer,
                Type::String,
                Type::Array,
                Type::Object,
            ],
            OpenAPIVersion::Version_3_1
        ];
    }

    /** @return Generator<array{ 0: string[], 1: OpenAPIVersion }> */
    public static function provideValuesForVersion(): Generator
    {
        foreach (self::provideCasesForVersion() as $dataSet => [$cases, $version]) {
            yield $dataSet => [
                array_map(fn($t) => $t->value, $cases),
                $version,
            ];
        }
    }

    /** @return Generator<array{ 0: ?Type, 1: OpenAPIVersion, 2: string}> */
    public static function provideStringsToTryFromVersion(): Generator
    {
        foreach (Type::cases() as $case) {
            yield "$case->value on 3.0" => [
                $case === Type::Null ? null : $case,
                OpenAPIVersion::Version_3_0,
                $case->value
            ];

            yield "$case->value on 3.1" => [
                $case,
                OpenAPIVersion::Version_3_1,
                $case->value
            ];
        }
    }
}
