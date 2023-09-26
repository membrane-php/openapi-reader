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
        $message = <<<TEXT
            A valid OpenAPI must contain unique operationIds across the API.
            The operationId: '$operationId' is duplicated between the following operations:
            Path: '$firstPath', Method: '$firstMethod'.
            Path: '$secondPath', Method: '$secondMethod'.
            TEXT;

        return new self($message, self::INVALID_OPEN_API);
    }

    public static function mustHaveSchemaXorContent(string $name): self
    {
        return new self(
            sprintf('Parameter "%s" MUST have either a Schema or Content, but not both.', $name),
            self::INVALID_OPEN_API
        );
    }

    public static function failedCebeValidation(string ...$errors): self
    {
        $message = sprintf("OpenAPI is invalid for the following reasons:\n\t- %s", implode("\n\t- ", $errors));
        return new self($message, self::INVALID_OPEN_API);
    }
}
