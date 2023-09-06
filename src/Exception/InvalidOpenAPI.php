<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use RuntimeException;

/*
 * This exception occurs if your Open API is invalid according to the OpenAPI Specification
 */
final class InvalidOpenAPI extends RuntimeException
{
    public const INVALID_OPEN_API = 0;

    public static function duplicateOperationIds(
        string $operationId,
        string $firstPath,
        string $firstMethod,
        string $secondPath,
        string $secondMethod
    ): self {
        $message = sprintf(
            "A valid OpenAPI must contain unique operationIds across the API.\n" .
            "The operationId: '%s' is duplicated between the following operations:\n" .
            "\tPath: '%s', Method: '%s'\n" .
            "\tPath: '%s', Method: '%s'\n",
            $operationId,
            $firstPath,
            $firstMethod,
            $secondPath,
            $secondMethod
        );

        return new self($message, self::INVALID_OPEN_API);
    }

    public static function failedCebeValidation(string ...$errors): self
    {
        $message = sprintf("OpenAPI is invalid for the following reasons:\n\t- %s", implode("\n\t- ", $errors));
        return new self($message, self::INVALID_OPEN_API);
    }
}
