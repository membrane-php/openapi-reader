<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use RuntimeException;

/*
 * This exception occurs if your Open API is readable but cannot be supported by Membrane.
 */
final class CannotSupport extends RuntimeException
{
    public const UNSUPPORTED_METHOD = 0;
    public const UNSUPPORTED_VERSION = 1;
    public const MISSING_OPERATION_ID = 2;

    public static function unsupportedMethod(string $pathUrl, string $method): self
    {
        $message = <<<TEXT
            Membrane does not currently support the method: '$method'.
            Found on Path: '$pathUrl'
            TEXT;
        return new self($message, self::UNSUPPORTED_METHOD);
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
}
