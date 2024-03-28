<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Enum;

use Membrane\OpenAPIReader\OpenAPIVersion;

enum Type: string
{
    case Null = 'null';

    case Boolean = 'boolean';
    case Integer = 'integer';
    case Number = 'number';
    case String = 'string';

    case Array = 'array';
    case Object = 'object';

    /** @return self[] */
    public static function casesSupportedByVersion(
        OpenAPIVersion $version
    ): array {
        return match ($version) {
            OpenAPIVersion::Version_3_0 => [
                Type::Boolean,
                Type::Number,
                Type::Integer,
                Type::String,
                Type::Array,
                Type::Object,
            ],
            OpenAPIVersion::Version_3_1 => [
                Type::Null,
                Type::Boolean,
                Type::Number,
                Type::Integer,
                Type::String,
                Type::Array,
                Type::Object,
            ]
        };
    }

    /** @return string[] */
    public static function valuesSupportedByVersion(
        OpenAPIVersion $version
    ): array {
        return array_map(
            fn($t) => $t->value,
            self::casesSupportedByVersion($version)
        );
    }

    public static function tryFromCasesSupportedByVersion(
        OpenAPIVersion $version,
        string $type
    ): ?self {
        $type = self::tryFrom($type);

        return in_array($type, self::casesSupportedByVersion($version)) ?
            $type :
            null;
    }
}
