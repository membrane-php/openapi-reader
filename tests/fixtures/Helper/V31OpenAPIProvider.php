<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\OpenAPI;

final class V31OpenAPIProvider
{
    /**
     * This will return a "minimal" JSON string OpenAPI
     * Functions prefixed with "minimalV31" return equivalent OpenAPI
     */
    public static function minimalV31String(): string
    {
        $string = json_encode([
            'openapi' => '3.1.0',
            'info' => ['title' => 'My Minimal OpenAPI', 'version' => '1.0.0'],
            'paths' => []
        ]);

        assert(is_string($string));

        return $string;
    }

    /**
     * This will return a "minimal" Membrane OpenAPI object
     * Functions prefixed with "minimalV31" return equivalent OpenAPI
     */
    public static function minimalV31MembraneObject(): OpenAPI
    {
        return OpenAPI::fromPartial(new Partial\OpenAPI(
            openAPI: '3.1.0',
            title: 'My Minimal OpenAPI',
            version: '1.0.0',
            paths: []
        ));
    }

    /**
     * This will return a "detailed" JSON string OpenAPI
     * Functions prefixed with "detailedV31" return equivalent OpenAPI
     */
    public static function detailedV31String(): string
    {
        $string = json_encode([
            'openapi' => '3.1.0',
            'info' => ['title' => 'My Detailed OpenAPI', 'version' => '1.0.1'],
            'servers' => [
                [
                    'url' => 'https://server.net/{version}',
                    'variables' => [
                        'version' => [
                            'default' => '2.1',
                            'enum' => ['2.0', '2.1', '2.2',]
                        ]
                    ]
                ]
            ],
            'paths' => [
                '/first' => [
                    'parameters' => [
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'required' => false,
                            'schema' => [
                                'type' => ['integer', 'string'],
                                'minimum' => 5,
                                'exclusiveMinimum' => 6,
                                'maximum' => 8.3,
                                'exclusiveMaximum' => 8.21,
                                'maxLength' => 2,
                                'minLength' => 1,
                                'pattern' => '.*',
                                'maxItems' => 5,
                                'minItems' => 3,
                                'uniqueItems' => true,
                                'maxContains' => 5,
                                'minContains' => 2,
                                'maxProperties' => 6,
                                'minProperties' => 2,
                                'required' => ['property1', 'property2'],
                                'dependentRequired' => ['property2' => ['property3']],
                                'not' => ['type' => 'integer'],
                            ]
                        ]
                    ],
                    'get' => [
                        'operationId' => 'first-get',
                        'parameters' => [
                            [
                                'name' => 'pet',
                                'in' => 'header',
                                'required' => true,
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['type' => 'integer'],
                                                ['type' => 'number'],
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful Response'
                            ]
                        ]
                    ]
                ],
                '/second' => [
                    'servers' => [
                        ['url' => 'https://second-server.co.uk']
                    ],
                    'parameters' => [
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'get' => [
                        'operationId' => 'second-get',
                        'parameters' => [
                            [
                                'name' => 'pet',
                                'in' => 'header',
                                'required' => true,
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['type' => 'integer'],
                                                ['type' => 'number']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful Response'
                            ]
                        ]
                    ],
                    'put' => [
                        'operationId' => 'second-put',
                        'servers' => [
                            ['url' => 'https://second-put.com']
                        ],
                        'parameters' => [[
                            'name' => 'user',
                            'in' => 'cookie',
                            'schema' => ['type' => 'object'],
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful Response'
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        assert(is_string($string));

        return $string;
    }

    /**
     * This will return a "detailed" Membrane\OpenAPIReader\ValueObject\Valid\V31\OpenAPI object
     * Functions prefixed with "detailedV31" return equivalent OpenAPI
     */
    public static function detailedV31MembraneObject(): OpenAPI
    {
        return OpenAPI::fromPartial(new Partial\OpenAPI(
            openAPI: '3.1.0',
            title: 'My Detailed OpenAPI',
            version: '1.0.1',
            servers: [new Partial\Server(
                url: 'https://server.net/{version}',
                variables: [new Partial\ServerVariable(
                    name: 'version',
                    default: '2.1',
                    enum: ['2.0', '2.1', '2.2'],
                )]
            )],
            paths: [
                new Partial\PathItem(
                    path: '/first',
                    parameters: [new Partial\Parameter(
                        name: 'limit',
                        in: 'query',
                        required: false,
                        schema: new Partial\Schema(
                            type: ['integer', 'string'],
                            exclusiveMaximum: 8.21,
                            exclusiveMinimum: 6,
                            maximum: 8.3,
                            minimum: 5,
                            maxLength: 2,
                            minLength: 1,
                            pattern: '.*',
                            maxItems: 5,
                            minItems: 3,
                            uniqueItems: true,
                            maxContains: 5,
                            minContains: 2,
                            maxProperties: 6,
                            minProperties: 2,
                            required: ['property1', 'property2'],
                            dependentRequired: ['property2' => ['property3']],
                            not: new Partial\Schema(type: 'integer'),
                        )
                    )],
                    get: new Partial\Operation(
                        operationId: 'first-get',
                        parameters: [new Partial\Parameter(
                            name: 'pet',
                            in: 'header',
                            required: true,
                            schema: null,
                            content: [new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    allOf: [
                                        new Partial\Schema(type: 'integer'),
                                        new Partial\Schema(type: 'number')
                                    ]
                                )
                            )]
                        )],
                        responses: [
                            '200' => new Partial\Response(
                                description: 'Successful Response',
                            )
                        ],
                    )
                ),
                new Partial\PathItem(
                    path: '/second',
                    servers: [new Partial\Server(
                        url: 'https://second-server.co.uk'
                    )],
                    parameters: [new Partial\Parameter(
                        name: 'limit',
                        in: 'query',
                        required: false,
                        schema: new Partial\Schema(type: 'integer')
                    )],
                    get: new Partial\Operation(
                        operationId: 'second-get',
                        parameters: [new Partial\Parameter(
                            name: 'pet',
                            in: 'header',
                            required: true,
                            schema: null,
                            content: [new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    allOf: [
                                        new Partial\Schema(type: 'integer'),
                                        new Partial\Schema(type: 'number'),
                                    ]
                                )
                            )],
                        )],
                        responses: [
                            '200' => new Partial\Response(
                                description: 'Successful Response',
                            )
                        ],
                    ),
                    put: new Partial\Operation(
                        operationId: 'second-put',
                        servers: [new Partial\Server(
                            url: 'https://second-put.com'
                        )],
                        parameters: [new Partial\Parameter(
                            name: 'user',
                            in: 'cookie',
                            required: false,
                            schema: new Partial\Schema(type: 'object'),
                        )],
                        responses: [
                            '200' => new Partial\Response(
                                description: 'Successful Response',
                            )
                        ],
                    )
                )
            ]
        ));
    }
}
