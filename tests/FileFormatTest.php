<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests;

use Generator;
use Membrane\OpenAPIReader\FileFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileFormat::class)]
class FileFormatTest extends TestCase
{
    public static function provideSupportedFileExtensions(): Generator
    {
        yield 'json' => [FileFormat::Json, 'json'];
        yield 'JSON' => [FileFormat::Json, 'JSON'];
        yield 'yaml' => [FileFormat::Yaml, 'yaml'];
        yield 'YAML' => [FileFormat::Yaml, 'YAML'];
        yield 'yml' => [FileFormat::Yaml, 'yml'];
        yield 'YML' => [FileFormat::Yaml, 'YML'];
    }

    #[Test, TestDox('it supports file extensions used for OpenAPI')]
    #[DataProvider('provideSupportedFileExtensions')]
    public function itCanConstructFromSupportedFileExtensions(FileFormat $expected, string $fileExtension): void
    {
        self::assertEquals($expected, FileFormat::fromFileExtension($fileExtension));
    }

    public static function provideUnsupportedFileExtensions(): Generator
    {
        yield 'php' => ['php'];
        yield 'xml' => ['xml'];
    }

    #[Test, TestDox('it supports file extensions used for OpenAPI')]
    #[DataProvider('provideUnsupportedFileExtensions')]
    public function itCanConstructFromUnsupportedFileExtensions(string $fileExtension): void
    {
        self::assertNull(FileFormat::fromFileExtension($fileExtension));
    }
}
