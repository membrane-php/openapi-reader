<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Enum;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;

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
    public static function casesForVersion(OpenAPIVersion $version): array
    {
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
    public static function valuesForVersion(OpenAPIVersion $version): array
    {
        return array_map(fn($t) => $t->value, self::casesForVersion($version));
    }

    public static function fromVersion(
        Identifier $identifier,
        OpenAPIVersion $version,
        string $type
    ): self {
        return self::tryFromVersion($version, $type) ??
            throw InvalidOpenAPI::invalidType($identifier, $type);
    }

    public static function tryFromVersion(
        OpenAPIVersion $version,
        string $type
    ): ?self {
        $type = self::tryFrom($type);

        return in_array($type, self::casesForVersion($version)) ?
            $type :
            null;
    }

    public function isPrimitive(): bool
    {
        return match ($this) {
            self::Array,
            self::Object => false,
            default => true,
        };
    }
}
