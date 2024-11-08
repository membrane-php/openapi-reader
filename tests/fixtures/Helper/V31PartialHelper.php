<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

use Membrane\OpenAPIReader\ValueObject\Partial\MediaType;
use Membrane\OpenAPIReader\ValueObject\Partial\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial\Operation;
use Membrane\OpenAPIReader\ValueObject\Partial\Parameter;
use Membrane\OpenAPIReader\ValueObject\Partial\PathItem;
use Membrane\OpenAPIReader\ValueObject\Partial\Schema;
use Membrane\OpenAPIReader\ValueObject\Partial\Server;
use Membrane\OpenAPIReader\ValueObject\Partial\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Value;

final class V31PartialHelper
{
    public static function createOpenAPI(
        ?string $openapi = '3.0.0',
        ?string $title = 'Test API',
        ?string $version = '1.0.0',
        array $servers = [],
        ?array $paths = [],
    ): OpenAPI {
        return new OpenAPI(
            $openapi,
            $title,
            $version,
            $servers,
            $paths,
        );
    }

    public static function createServer(
        ?string $url = 'https://www.server.net',
        ?array $variables = [],
    ): Server{
        return new Server(
            $url,
            $variables,
        );
    }

    public static function createServerVariable(
        ?string $name = 'default-name',
        ?string $default = 'default-value',
        ?array $enum = null,
    ): ServerVariable {
        return new ServerVariable(
            $name,
            $default,
            $enum
        );
    }

    public static function createPathItem(
        ?string $path = '/path',
        array $servers = [],
        array $parameters = [],
         ?Operation $get = null,
         ?Operation $put = null,
         ?Operation $post = null,
         ?Operation $delete = null,
         ?Operation $options = null,
         ?Operation $head = null,
         ?Operation $patch = null,
         ?Operation $trace = null,
    ): PathItem {
        return new PathItem(
            $path,
            $servers,
            $parameters,
            $get,
            $put,
            $post,
            $delete,
            $options,
            $head,
            $patch,
            $trace,
        );
    }

    /** @param MediaType[] $content*/
    public static function createParameter(
        ?string $name = 'test-param',
        ?string $in = 'path',
        ?bool $required = true,
        ?string $style = null,
        ?bool $explode = null,
        ?Schema $schema = new Schema(),
        array $content = [],
    ): Parameter {
        return new Parameter(
            $name,
            $in,
            $required,
            $style,
            $explode,
            $schema,
            $content
        );
    }

    public static function createOperation(
        ?string $operationId = 'test-id',
        array $servers = [],
        array $parameters = []
    ): Operation {
        return new Operation(
            $operationId,
            $servers,
            $parameters
        );
    }

    public static function createMediaType(
        ?string $mediaType = 'application/json',
        ?Schema $schema = null,
    ): MediaType {
        return new MediaType(
            $mediaType,
            $schema
        );
    }

    /**
     * @param null|array<string>|string $type
     * @param Value[]|null $enum
     * @param null|Schema[] $allOf
     * @param null|Schema[] $anyOf
     * @param null|Schema[] $oneOf
     * @param string[] $required
     * @param string[][] $dependentRequired
     * @param Schema[] $dependentSchemas
     * @return Schema
     */
    public static function createSchema(
        null|array|string $type = null,
        array|null $enum = null,
        Value|null $const = null,
        float|int|null $multipleOf = null,
        float|int|null $exclusiveMaximum = null,
        float|int|null $exclusiveMinimum = null,
        float|int|null $maximum = null,
        float|int|null $minimum = null,
        int|null $maxLength = null,
        int $minLength = 0,
        string|null $pattern = null,
        int|null $maxItems = null,
        int $minItems = 0,
        bool $uniqueItems = false,
        int|null $maxContains = null,
        int $minContains = 1,
        int|null $maxProperties = null,
        int $minProperties = 0,
        array $required = [],
        array $dependentRequired = [],
        ?array $allOf = null,
        ?array $anyOf = null,
        ?array $oneOf = null,
        Schema|null $not = null,
        Schema|null $if = null,
        Schema|null $then = null,
        Schema|null $else = null,
        array $dependentSchemas = [],
        array $prefixItems = [],
        Schema|null $items = null,
        Schema|null $contains = null,
    ): Schema {
        return new Schema(
            type: $type,
            enum: $enum,
            const: $const,
            multipleOf: $multipleOf,
            exclusiveMaximum: $exclusiveMaximum,
            exclusiveMinimum: $exclusiveMinimum,
            maximum: $maximum,
            minimum: $minimum,
            maxLength: $maxLength,
            minLength: $minLength,
            pattern: $pattern,
            maxItems: $maxItems,
            minItems: $minItems,
            uniqueItems: $uniqueItems,
            maxContains: $maxContains,
            minContains: $minContains,
            maxProperties: $maxProperties,
            minProperties: $minProperties,
            required: $required,
            dependentRequired: $dependentRequired,
            allOf: $allOf,
            anyOf: $anyOf,
            oneOf: $oneOf,
            not: $not,
            if: $if,
            then: $then,
            else: $else,
            dependentSchemas: $dependentSchemas,
            prefixItems: $prefixItems,
            items: $items,
            contains: $contains,
        );
    }
}
