<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Factory\V30;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\ValueObject\Partial\MediaType;
use Membrane\OpenAPIReader\ValueObject\Partial\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial\Operation;
use Membrane\OpenAPIReader\ValueObject\Partial\Parameter;
use Membrane\OpenAPIReader\ValueObject\Partial\PathItem;
use Membrane\OpenAPIReader\ValueObject\Partial\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid;

final class FromCebe
{
    public static function createOpenAPI(
        Cebe\OpenApi $openApi
    ): Valid\V30\OpenAPI {

        /**
         * todo when phpstan 1.11 stable is released
         * replace the below lines with @phpstan-ignore nullsafe.neverNull
         * The reason for this is the cebe library does not specify that info is nullable
         * However it is not always set, so it can be null
         */
        return new Valid\V30\OpenAPI(new OpenAPI(
            $openApi->openapi,
            $openApi->info?->title, // @phpstan-ignore-line
            $openApi->info?->version, // @phpstan-ignore-line
            self::createPaths($openApi->paths)
        ));
    }

    /**
     * @param null|Cebe\Paths<string,Cebe\PathItem> $paths
     * @return PathItem[]
     */
    private static function createPaths(?Cebe\Paths $paths): array
    {
        $result = [];

        foreach ($paths ?? [] as $path => $pathItem) {
            $result[] = new PathItem(
                path: $path,
                parameters: self::createParameters($pathItem->parameters),
                get: self::createOperation($pathItem->get),
                put: self::createOperation($pathItem->put),
                post: self::createOperation($pathItem->post),
                delete: self::createOperation($pathItem->delete),
                options: self::createOperation($pathItem->options),
                head: self::createOperation($pathItem->head),
                patch: self::createOperation($pathItem->patch),
                trace: self::createOperation($pathItem->trace),
            );
        }

        return $result;
    }

    /**
     * @param Cebe\Parameter[]|Cebe\Reference[] $parameters
     * @return Parameter[]
     */
    private static function createParameters(array $parameters): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            assert(!$parameter instanceof Cebe\Reference);

            $result[] = new Parameter(
                $parameter->name,
                $parameter->in,
                $parameter->required,
                $parameter->style,
                $parameter->explode,
                self::createSchema($parameter->schema),
                self::createContent($parameter->content),
            );
        }

        return $result;
    }

    private static function createSchema(
        Cebe\Reference|Cebe\Schema|null $schema
    ): ?Schema {
        assert(!$schema instanceof Cebe\Reference);

        if ($schema === null) {
            return null;
        }

        $createSchemas = fn($schemas) => array_filter(
            array_map(fn($s) => self::createSchema($s), $schemas),
            fn($s) => $s !== null,
        );

        return new Schema(
            $schema->type,
            isset($schema->allOf) ? $createSchemas($schema->allOf) : null,
            isset($schema->anyOf) ? $createSchemas($schema->anyOf) : null,
            isset($schema->oneOf) ? $createSchemas($schema->oneOf) : null,
        );
    }

    /**
     * @param Cebe\MediaType[] $mediaTypes
     * @return MediaType[]
     */
    private static function createContent(array $mediaTypes): array
    {
        $result = [];

        foreach ($mediaTypes as $mediaType => $mediaTypeObject) {
            assert(!$mediaTypeObject->schema instanceof Cebe\Reference);

            $result[] = new MediaType(
                is_string($mediaType) ? $mediaType : null,
                !is_null($mediaTypeObject->schema) ?
                    self::createSchema($mediaTypeObject->schema) :
                    null
            );
        }

        return $result;
    }

    private static function createOperation(
        ?Cebe\Operation $operation
    ): ?Operation {
        if (is_null($operation)) {
            return null;
        }

        return new Operation(
            $operation->operationId,
            self::createParameters($operation->parameters)
        );
    }
}
