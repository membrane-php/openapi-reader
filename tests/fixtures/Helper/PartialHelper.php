<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

use Membrane\OpenAPIReader\ValueObject\Partial\MediaType;
use Membrane\OpenAPIReader\ValueObject\Partial\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial\Operation;
use Membrane\OpenAPIReader\ValueObject\Partial\Parameter;
use Membrane\OpenAPIReader\ValueObject\Partial\PathItem;
use Membrane\OpenAPIReader\ValueObject\Partial\Schema;

final class PartialHelper
{
    public static function createOpenAPI(
        ?string $openapi = '3.0.0',
        ?string $title = 'Test API',
        ?string $version = '1.0.0',
        array $paths = [],
    ): OpenAPI {
        return new OpenAPI(
            $openapi,
            $title,
            $version,
            $paths,
        );
    }

    public static function createPathItem(
        ?string $path = '/path',
        array $parameters = [],
        array $operations = [],
    ): PathItem {
        return new PathItem(
            $path,
            $parameters,
            $operations
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
        string $method = 'get',
        ?string $operationId = 'test-id',
        array $parameters = []
    ): Operation {
        return new Operation(
            $method,
            $operationId,
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
     * @param null|Schema[] $allOf
     * @param null|Schema[] $anyOf
     * @param null|Schema[] $oneOf
     * @return Schema
     */
    public static function createSchema(
        ?string $type = null,
        ?array $allOf = null,
        ?array $anyOf = null,
        ?array $oneOf = null,
    ): Schema {
        return new Schema(
            $type,
            $allOf,
            $anyOf,
            $oneOf,
        );
    }
}
