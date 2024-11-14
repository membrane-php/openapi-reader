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
use Membrane\OpenAPIReader\ValueObject\Partial\Server;
use Membrane\OpenAPIReader\ValueObject\Partial\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;
use Membrane\OpenAPIReader\ValueObject\Value;

final class FromCebe
{
    public static function createOpenAPI(
        Cebe\OpenApi $openApi
    ): V30\OpenAPI {
        $servers = count($openApi->servers) === 1 && $openApi->servers[0]->url === '/' ?
            [] :
            $openApi->servers;

        /**
         * todo when phpstan 1.11 stable is released
         * replace the below lines with phpstan-ignore nullsafe.neverNull
         * The reason for this is the cebe library does not specify that info is nullable
         * However it is not always set, so it can be null
         */
        return V30\OpenAPI::fromPartial(new OpenAPI(
            $openApi->openapi,
            $openApi->info?->title, // @phpstan-ignore-line
            $openApi->info?->version, // @phpstan-ignore-line
            self::createServers($servers),
            self::createPaths($openApi->paths)
        ));
    }

    /**
     * @param Cebe\Server[] $servers
     * @return Server[]
     */
    private static function createServers(array $servers): array
    {
        $result = [];

        foreach ($servers as $server) {
            $result[] = new Server(
                $server->url,
                self::createServerVariables($server->variables)
            );
        }

        return $result;
    }

    /**
     * @param Cebe\ServerVariable[] $serverVariables
     * @return ServerVariable[]
     */
    private static function createServerVariables(array $serverVariables): array
    {
        $result = [];

        foreach ($serverVariables as $name => $serverVariable) {
            $result[] = new ServerVariable(
                $name,
                $serverVariable->default,
                $serverVariable->enum,
            );
        }

        return $result;
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
                servers: self::createServers($pathItem->servers),
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
            type: $schema->type,
            enum: $schema->enum ?? null,
            const: $schema->const ?? null,
            default: isset($schema->default) ? new Value($schema->default) : null,
            nullable: $schema->nullable ?? false,
            multipleOf: $schema->multipleOf ?? null,
            exclusiveMaximum: $schema->exclusiveMaximum ?? false,
            exclusiveMinimum: $schema->exclusiveMinimum ?? false,
            maximum: $schema->maximum ?? null,
            minimum: $schema->minimum ?? null,
            maxLength: $schema->maxLength ?? null,
            minLength: $schema->minLength ?? 0,
            pattern: $schema->pattern ?? null,
            maxItems: $schema->maxItems ?? null,
            minItems: $schema->minItems ?? 0,
            uniqueItems: $schema->uniqueItems ?? false,
            maxContains: $schema->maxContains ?? null,
            minContains: $schema->minContains ?? null,
            maxProperties: $schema->maxProperties ?? null,
            minProperties: $schema->minProperties ?? 0,
            required: $schema->required ?? null,
            dependentRequired: $schema->dependentRequired ?? null,
            allOf: isset($schema->allOf) ? $createSchemas($schema->allOf) : null,
            anyOf: isset($schema->anyOf) ? $createSchemas($schema->anyOf) : null,
            oneOf: isset($schema->oneOf) ? $createSchemas($schema->oneOf) : null,
            not: isset($schema->not) ? self::createSchema($schema->not) : null,
            if: isset($schema->if) ? self::createSchema($schema->if) : null,
            then: isset($schema->then) ? self::createSchema($schema->then) : null,
            else: isset($schema->else) ? self::createSchema($schema->else) : null,
            dependentSchemas: isset($schema->dependentSchemas) ?
                $createSchemas($schema->dependentSchemas) :
                null,
            items: isset($schema->items) ? self::createSchema($schema->items) : null,
            properties: isset($schema->properties) ? $createSchemas($schema->properties) : [],
            additionalProperties: isset($schema->additionalProperties) ? (is_bool($schema->additionalProperties) ?
                $schema->additionalProperties :
                self::createSchema($schema->additionalProperties) ?? true) :
                true,
            format: $schema->format ?? null,
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
            operationId: $operation->operationId,
            servers: self::createServers($operation->servers),
            parameters: self::createParameters($operation->parameters)
        );
    }
}
