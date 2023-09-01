<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

enum FileFormat
{
    case Json;
    case Yaml;

    public static function fromFileExtension(string $fileExtension): ?self
    {
        return match (strtolower($fileExtension)) {
            'json' => self::Json,
            'yaml', 'yml' => self::Yaml,
            default => null
        };
    }
}
