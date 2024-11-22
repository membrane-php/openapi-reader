<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

final class ProvidesPetstoreApi
{
    private const API = __DIR__ . '/petstore.yaml';

    /**
     * @return \Generator<array{
     *     0: string,
     *     1: string,
     *     2: Method,
     *     3: V30\Operation,
     * }>
     */
    public static function provideOperations(): Generator
    {
        yield 'listPets' => [self::API, '/pets', Method::GET, self::listPets()];

        yield 'createPets' => [self::API, '/pets', Method::POST, self::createPets()];

        yield 'showPetById' => [self::API, '/pets/{petId}', Method::GET, self::showPetById()];
    }

    private static function listPets(): V30\Operation
    {
        return V30\Operation::fromPartial(
            parentIdentifier: new Identifier('Swagger Petstore(1.0.0)', '/pets'),
            pathServers: [new V30\Server(
                new Identifier('Swagger Petstore(1.0.0)'),
                new Partial\Server(url: 'http://petstore.swagger.io/v1'),
            )],
            pathParameters: [],
            method: Method::GET,
            operation: new Partial\Operation('listPets', [], [
                new Partial\Parameter(
                    name: 'limit',
                    in: 'query',
                    schema: new Partial\Schema(type: 'integer', maximum: 100, format: 'int32'),
                ),
            ])
        );
    }

    private static function createPets(): V30\Operation
    {
        return V30\Operation::fromPartial(
            parentIdentifier: new Identifier('Swagger Petstore(1.0.0)', '/pets'),
            pathServers: [new V30\Server(
                new Identifier('Swagger Petstore(1.0.0)'),
                new Partial\Server(url: 'http://petstore.swagger.io/v1'),
            )],
            pathParameters: [],
            method: Method::POST,
            operation: new Partial\Operation('createPets', [], [], new Partial\RequestBody(
                content: [new Partial\MediaType(
                    contentType: 'application/json',
                    schema: new Partial\Schema(
                        type: 'object',
                        required: ['id', 'name'],
                        properties: [
                            'id' => new Partial\Schema(type: 'integer', format: 'int64'),
                            'name' => new Partial\Schema(type: 'string'),
                            'tag' => new Partial\Schema(type: 'string'),
                        ],
                    )
                )],
                required: true,
            )),
        );
    }

    private static function showPetById(): V30\Operation
    {
        return V30\Operation::fromPartial(
            parentIdentifier: new Identifier('Swagger Petstore(1.0.0)', '/pets/{petId}'),
            pathServers: [new V30\Server(
                new Identifier('Swagger Petstore(1.0.0)'),
                new Partial\Server(url: 'http://petstore.swagger.io/v1'),
            )],
            pathParameters: [],
            method: Method::GET,
            operation: new Partial\Operation('showPetById', [], [
                new Partial\Parameter(
                    name: 'petId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'string'),
                ),
            ])
        );
    }
}
