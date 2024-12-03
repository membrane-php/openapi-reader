<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;

/*
 * This exception occurs if your Open API is invalid according to the OpenAPI Specification
 */
final class InvalidOpenAPI extends RuntimeException
{
    public static function missingRequiredField(
        Identifier $identifier,
        string $requiredField,
    ): self {
        return new InvalidOpenAPI(<<<TEXT
            $identifier
              - Missing required field "$requiredField"
            TEXT);
    }

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

    public static function serverMissingUrl(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            Every Server Object MUST specify a "url"
            TEXT;

        return new self($message);
    }

    public static function serverHasUndefinedVariables(
        Identifier $identifier,
        string ...$variables
    ): self {
        $variables = implode(
            "\n",
            array_map(fn($v) => sprintf('- "%s"', $v), $variables)
        );
        $message = <<<TEXT
            $identifier
            "url" names variable(s) that have not been defined by "variables":
            $variables
            TEXT;

        return new self($message);
    }

    public static function urlNestedVariable(Identifier $identifier): self
    {
        return new self(
            <<<TEXT
            $identifier
            Templated URL contains nested expressions.
            RFC6570 - 3.2: Expressions cannot be nested.
            TEXT
        );
    }

    public static function urlLiteralClosingBrace(Identifier $identifier): self
    {
        return new self(
            <<<TEXT
            $identifier
            URL contains a closing brace used outside of a variable.
            RFC6750 - 2.1: Braces used outside of variables should be pct-encoded
            TEXT
        );
    }

    public static function urlUnclosedVariable(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            URL contains an unclosed variable.
            RFC6750 - 3.2: A variable begins with an opening brace "{" and continues until the closing brace "}"
            TEXT;

        return new self($message);
    }

    public static function serverVariableMissingName(
        Identifier $identifier
    ): self {
        $message = <<<TEXT
            $identifier
            Every Server Variable Object MUST be mapped to by its name
            TEXT;

        return new self($message);
    }

    public static function serverVariableMissingDefault(
        Identifier $identifier
    ): self {
        $message = <<<TEXT
            $identifier
            Every Server Variable Object MUST specify a "default"
            TEXT;

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

    public static function mustHaveSchemaXorContent(Identifier $identifier): self
    {
        return new self(<<<TEXT
            $identifier
            Parameter MUST have either a Schema or Content, but not both.
            TEXT);
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

    public static function deepObjectMustBeObject(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            style:deepObject is only applicable to schemas of type:object
            Therefore, your schema MUST be valid ONLY as type:object
            TEXT;

        return new self($message);
    }
    public static function invalidType(Identifier $identifier, string $type): self
    {
        $message = <<<TEXT
            $identifier
            "type" MUST be "boolean", "object", "array", "number", "string", or "integer".
            "$type" is invalid.
            TEXT;

        return new self($message);
    }

    public static function keywordMustBeType(
        Identifier $identifier,
        string $keyword,
        Type $type,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be $type->value
            TEXT;

        return new self($message);
    }

    public static function numericExclusiveMinMaxIn30(
        Identifier $identifier,
        string $keyword,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be a boolean in OpenAPI ^3.0
            TEXT;

        return new self($message);
    }

    public static function arrayItemsIn31(Identifier $identifier): self
    {
        $message = <<<TEXT
            $identifier
            items MUST be a Schema in OpenAPI ^3.1
            TEXT;

        return new self($message);
    }

    public static function boolExclusiveMinMaxIn31(
        Identifier $identifier,
        string $keyword,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be a number in OpenAPI ^3.1
            TEXT;

        return new self($message);
    }

    public static function keywordCannotBeZero(
        Identifier $identifier,
        string $keyword,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST not be zero
            TEXT;

        return new self($message);
    }

    public static function keywordMustBeNonNegativeInteger(
        Identifier $identifier,
        string $keyword,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be greater than or equal to zero
            TEXT;

        return new self($message);
    }

    public static function failedCebeValidation(string ...$errors): self
    {
        $message = sprintf("OpenAPI is invalid for the following reasons:\n\t- %s", implode("\n\t- ", $errors));
        return new self($message);
    }

    public static function mustBeNonEmpty(
        Identifier $identifier,
        string $keyword
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be a non-empty array
            TEXT;

        return new self($message);
    }

    public static function mustContainUniqueItems(
        Identifier $identifier,
        string $keyword
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be an array of unique strings
            TEXT;

        return new self($message);
    }

    public static function mustSpecifyItemsForArrayType(
        Identifier $identifier,
    ): self {
        $message = <<<TEXT
            $identifier
            items MUST be specified if type is array
            TEXT;

        return new self($message);
    }

    public static function mustHaveStringKeys(
        Identifier $identifier,
        string $keyword,
    ): self {
        $message = <<<TEXT
            $identifier
            $keyword MUST be specified an array with string keys
            TEXT;

        return new self($message);
    }

    public static function responseCodeMustBeNumericOrDefault(
        Identifier $identifier,
        string $code,
    ): self {
        $message = <<<TEXT
            $identifier
            Response code MUST be numeric, or "default". 
            "$code" is invalid.
            TEXT;

        return new self($message);
    }

    public static function defaultMustConformToType(
        Identifier $identifier,
    ): self {
        return new self(<<<TEXT
            $identifier
              - default MUST conform to type
            TEXT);
    }
}
