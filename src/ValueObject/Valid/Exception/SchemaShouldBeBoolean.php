<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Exception;

use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;

/*
 * This exception occurs when an error occurs reading all, or part, of the file provided.
 * This may be due to one of the following reasons:
 * 1: The file, or its references, cannot be found
 * 2: The data is invalid according to the associated format's specification
 */
final class SchemaShouldBeBoolean extends RuntimeException
{
    public const ALWAYS_TRUE = 0;
    public const ALWAYS_FALSE = 1;

    public static function alwaysTrue(
        Identifier $identifier,
        string $reason,
    ): SchemaShouldBeBoolean {
        return new SchemaShouldBeBoolean(
            <<<TEXT
            $identifier is impossible to fail and will be simplified to true
              - $reason
            TEXT,
            SchemaShouldBeBoolean::ALWAYS_TRUE,
        );
    }

    public static function alwaysFalse(
        Identifier $identifier,
        string $reason,
    ): SchemaShouldBeBoolean {
        return new SchemaShouldBeBoolean(
            <<<TEXT
            $identifier is impossible to pass and will be simplified to false
              - $reason
            TEXT,
            SchemaShouldBeBoolean::ALWAYS_FALSE,
        );
    }
}
