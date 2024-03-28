<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;

/*
 * This exception occurs if your Open API is readable but cannot be supported by Membrane.
 */

final class CannotSupport extends RuntimeException
{
    public const UNSUPPORTED_VERSION = 0;
    public const MISSING_OPERATION_ID = 1;
    public const MISSING_TYPE_DECLARATION = 2;
    public const AMBIGUOUS_RESOLUTION = 3;
    public const CANNOT_PARSE = 4;

    public static function membraneReaderOnlySupportsv30(): self
    {
        $message = 'MembraneReader currently only supports Version 3.0.X';
        return new self($message, self::UNSUPPORTED_VERSION);
    }

    public static function noSupportedVersions(): self
    {
        $message = 'Reader cannot be constructed without any OpenAPI versions to support';
        return new self($message, self::UNSUPPORTED_VERSION);
    }

    public static function unsupportedVersion(string $version): self
    {
        $message = sprintf('"%s" has not been provided as a supported version', $version);
        return new self($message, self::UNSUPPORTED_VERSION);
    }

    public static function missingOperationId(string $pathUrl, string $method): self
    {
        $message = <<<TEXT
            Membrane requires an operationId on all operations.
            operationId is missing for the following operation:
            Path: '$pathUrl'
            Method: '$method'
            TEXT;
        return new self($message, self::MISSING_OPERATION_ID);
    }

    public static function undeclaredType(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            Membrane requires all schemas to have at least one of the following keywords:
            "type", "allOf", "anyOf", "oneOf" or "not"
            TEXT;

        return new self($message, self::MISSING_TYPE_DECLARATION);
    }

    public static function conflictingParameterStyles(string ...$parameters): self
    {
        $message = sprintf(
            <<<'TEXT'
            The following parameters lead to ambiguous resolution:
            %s
            TEXT,
            implode(",\n", $parameters)
        );

        return new self($message, self::AMBIGUOUS_RESOLUTION);
    }

    public static function unreadableContentType(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier defines a schema for validating payloads.
            Membrane cannot parse the prescribed content type.
            Membrane will need a different content type or the schema removed.
            TEXT;

        return new self($message, self::CANNOT_PARSE);
    }
}
