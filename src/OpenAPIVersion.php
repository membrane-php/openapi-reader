<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

enum OpenAPIVersion
{
    case Version_3_0;
    case Version_3_1;

    public static function fromString(string $version): ?self
    {
        if (str_starts_with($version, '3.0.')) {
            return self::Version_3_0;
        }

        if (str_starts_with($version, '3.1.')) {
            return self::Version_3_1;
        }

        return null;
    }
}
