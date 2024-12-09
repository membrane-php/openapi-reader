<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

use Membrane\OpenAPIReader\ValueObject\Partial\MediaType;
use Membrane\OpenAPIReader\ValueObject\Partial\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial\Operation;
use Membrane\OpenAPIReader\ValueObject\Partial\Parameter;
use Membrane\OpenAPIReader\ValueObject\Partial\PathItem;
use Membrane\OpenAPIReader\ValueObject\Partial\RequestBody;
use Membrane\OpenAPIReader\ValueObject\Partial\Response;
use Membrane\OpenAPIReader\ValueObject\Partial\Schema;
use Membrane\OpenAPIReader\ValueObject\Partial\Server;
use Membrane\OpenAPIReader\ValueObject\Partial\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Value;

final class PartialHelper
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

    /**
     * @param Server[] $servers
     * @param Parameter[] $parameters
     * @param Response[] $responses
     */
    public static function createOperation(
        ?string $operationId = 'test-id',
        array $servers = [],
        array $parameters = [],
        RequestBody $requestBody = null,
        array $responses = [],
    ): Operation {
        return new Operation(
            operationId: $operationId,
            servers: $servers,
            parameters: $parameters,
            requestBody: $requestBody,
            responses: $responses,
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
     * @param null|Value[] $enum
     * @param null|Schema[] $allOf
     * @param null|Schema[] $anyOf
     * @param null|Schema[] $oneOf
     * @param Schema[] $properties
     * @param null|string[] $required
     * @return Schema
     */
    public static function createSchema(
        string|null $type = null,
        bool $nullable = false,
        array|null $enum = null,
        Value|null $default = null,
        float|int|null $multipleOf = null,
        float|int|null $maximum = null,
        float|int|null $minimum = null,
        bool $exclusiveMaximum = false,
        bool $exclusiveMinimum = false,
        int|null $maxLength = null,
        int $minLength = 0,
        string|null $pattern = null,
        int|null $maxItems = null,
        int $minItems = 0,
        bool $uniqueItems = false,
        int|null $maxProperties = null,
        int $minProperties = 0,
        array|null $required = null,
        Schema|null $items = null,
        array $properties = [],
        array|null $allOf = null,
        array|null $anyOf = null,
        array|null $oneOf = null,
        bool|Schema|null $not = null,
        string $format = '',
        string $title = '',
        string $description = '',
    ): Schema {
        return new Schema(
            type: $type,
            enum: $enum,
            default: $default,
            nullable: $nullable,
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
            maxProperties: $maxProperties,
            minProperties: $minProperties,
            required: $required,
            allOf: $allOf,
            anyOf: $anyOf,
            oneOf: $oneOf,
            not: $not,
            items: $items,
            properties: $properties,
            format: $format,
            title: $title,
            description: $description,
        );
    }
}
