<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests;

use Generator;
use Membrane\OpenAPIReader\OpenAPIVersion;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox};
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIVersion::class)]
class OpenAPIVersionTest extends TestCase
{
    public static function provideSupportedStringVersions(): Generator
    {
        yield '3.0.1' => [OpenAPIVersion::Version_3_0, '3.0.1'];
        yield '3.0.2' => [OpenAPIVersion::Version_3_0, '3.0.2'];
        yield '3.0.3' => [OpenAPIVersion::Version_3_0, '3.0.3'];
        yield '3.1.0' => [OpenAPIVersion::Version_3_1, '3.1.0'];
    }

    #[Test, TestDox('it supports file extensions used for OpenAPI')]
    #[DataProvider('provideSupportedStringVersions')]
    public function itCanConstructFromSupportedFileExtensions(OpenAPIVersion $expected, string $version): void
    {
        self::assertEquals($expected, OpenAPIVersion::fromString($version));
    }

    public static function provideUnsupportedStringVersions(): Generator
    {
        yield '1.0.0' => [ '1.0.0'];
        yield '2.0.0' => ['2.0.0'];
    }

    #[Test, TestDox('it supports file extensions used for OpenAPI')]
    #[DataProvider('provideUnsupportedStringVersions')]
    public function itCannotConstructFromUnsupportedFileExtensions(string $version): void
    {
        self::assertNull(OpenAPIVersion::fromString($version));
    }
}
