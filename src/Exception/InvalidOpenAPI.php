<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;

/*
 * This exception occurs if your Open API is invalid according to the OpenAPI Specification
 */
final class InvalidOpenAPI extends RuntimeException
{
    public static function missingInfo(): self
    {
        $message = <<<TEXT
            An OpenAPI Object MUST contain an Info Object.
            That Info Object MUST contain a "title" and "version".
            TEXT;

        return new self($message);
    }

    public static function missingOpenAPIVersion(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            An OpenAPI Object MUST contain an "openapi" field.
            This is the version of the OpenAPI Specification you are implementing
            TEXT;

        return new self($message);
    }

    public static function missingPaths(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            An OpenAPI Object MUST contain an "paths" field.
            TEXT;

        return new self($message);
    }

    public static function forwardSlashMustPrecedePath(
        Identifier $identifier,
        string $path
    ): self {
        $message = <<<TEXT
            $identifier
            All paths must be preceded by a forward slash.
            "$path" should be changed to "/$path"
            TEXT;

        return new self($message);
    }

    public static function pathMissingEndPoint(Identifier $identifier): self
    {
        $message = "$identifier contains a PathItem which is not mapped to an endpoint";

        return new self($message);
    }

    public static function duplicateParameters(
        Identifier $identifier,
        Identifier $parameter,
        Identifier $otherParameter
    ): self {
        $message = <<<TEXT
            $identifier
            This MUST NOT contain duplicate Parameters.
            Parameter uniqueness is determined by "name" and "in"
            $parameter
            $otherParameter
            TEXT;

        return new self($message);
    }

    public static function parameterMissingName(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier contains a Parameter Object without a "name" field.
            All Parameter Objects MUST have a "name" field.
            TEXT;

        return new self($message);
    }

    public static function parameterMissingLocation(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier 
            This Parameter MUST have an "in" field specifying their location.
            Its value MUST be "path", "query", "header" or "cookie".
            TEXT;

        return new self($message);
    }

    public static function parameterInvalidLocation(Identifier $identifier): self
    {
        return self::parameterMissingLocation($identifier);
    }

    public static function parameterMissingRequired(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier 
            If the parameter location is "path", this property is REQUIRED and its value MUST be true.
            TEXT;

        return new self($message);
    }

    public static function parameterInvalidStyle(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier 
            This Parameter has an invalid value for its "style" field.
            TEXT;

        return new self($message);
    }

    public static function parameterIncompatibleStyle(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            This Parameter has an incompatible combination of "style" and "in". 
            TEXT;

        return new self($message);
    }

    public static function identicalEndpoints(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            Two paths have been specified with identical endpoints.
            TEXT;

        return new self($message);
    }

    public static function equivalentTemplates(Identifier $firstPath, Identifier $secondPath): self
    {
        $message = <<<TEXT
            Templated paths with the same hierarchy but different templated names MUST NOT exist as they are identical.
            The invalid paths are as follows:
            First Path: '$firstPath'.
            Second Path: '$secondPath'.
            TEXT;

        return new self($message);
    }

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

        return new self($message);
    }

    public static function mustHaveSchemaXorContent(string $name): self
    {
        return new self(
            sprintf('Parameter "%s" MUST have either a Schema or Content, but not both.', $name),
        );
    }

    public static function parameterContentCanOnlyHaveOneEntry(Identifier $identifier): self
    {
        return new self(
            sprintf('Parameter found at %s has a content map. The map MUST only contain one entry.', $identifier),
        );
    }

    public static function contentMissingMediaType(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            "content" array must contain mediaType => Media Type Object pairs
            TEXT;

        return new self($message);
    }

    public static function failedCebeValidation(string ...$errors): self
    {
        $message = sprintf("OpenAPI is invalid for the following reasons:\n\t- %s", implode("\n\t- ", $errors));
        return new self($message);
    }

    public static function emptyComplexSchema(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            'complex schemas MUST have atleast one sub-schema
            TEXT;

        return new self($message);
    }
}
