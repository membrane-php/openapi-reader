<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

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
        return OpenAPI::fromPartial(V31PartialHelper::createOpenAPI(
            openapi: '3.1.0',
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
                                // @todo cebe library does not convert these into schemas
                                // 'if' => ['type' => 'integer'],
                                // 'then' => ['type' => 'integer'],
                                // 'else' => ['type' => 'integer'],
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
        return OpenAPI::fromPartial(V31PartialHelper::createOpenAPI(
            openapi: '3.1.0',
            title: 'My Detailed OpenAPI',
            version: '1.0.1',
            servers: [
                V31PartialHelper::createServer(
                    url: 'https://server.net/{version}',
                    variables: [
                        V31PartialHelper::createServerVariable(
                            name: 'version',
                            default: '2.1',
                            enum: ['2.0', '2.1', '2.2'],
                        ),
                    ]
                ),
            ],
            paths: [
                V31PartialHelper::createPathItem(
                    path: '/first',
                    parameters: [
                        V31PartialHelper::createParameter(
                            name: 'limit',
                            in: 'query',
                            required: false,
                            schema: V31PartialHelper::createSchema(
                                type: ['integer', 'string'],
                                minimum: 5,
                                exclusiveMinimum: 6,
                                maximum: 8.3,
                                exclusiveMaximum: 8.21,
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
                                required:['property1', 'property2'],
                                dependentRequired: ['property2' => ['property3']],
                                not: V31PartialHelper::createSchema(type: 'integer'),
                                // if: V31PartialHelper::createSchema(type: 'integer'),
                                // then: V31PartialHelper::createSchema(type: 'integer'),
                                // else: V31PartialHelper::createSchema(type: 'integer'),
                            )
                        ),
                    ],
                    get: V31PartialHelper::createOperation(
                        operationId: 'first-get',
                        parameters: [
                            V31PartialHelper::createParameter(
                                name: 'pet',
                                in: 'header',
                                required: true,
                                schema: null,
                                content: [
                                    V31PartialHelper::createMediaType(
                                        mediaType: 'application/json',
                                        schema: V31PartialHelper::createSchema(
                                            allOf: [
                                                V31PartialHelper::createSchema(
                                                    type: 'integer'
                                                ),
                                                V31PartialHelper::createSchema(
                                                    type: 'number'
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        ]
                    )
                ),
                V31PartialHelper::createPathItem(
                    path: '/second',
                    servers: [
                        V31PartialHelper::createServer(
                            url: 'https://second-server.co.uk'
                        ),
                    ],
                    parameters: [
                        V31PartialHelper::createParameter(
                            name: 'limit',
                            in: 'query',
                            required: false,
                            schema: V31PartialHelper::createSchema(
                                type: 'integer'
                            )
                        ),
                    ],
                    get: V31PartialHelper::createOperation(
                        operationId: 'second-get',
                        parameters: [
                            V31PartialHelper::createParameter(
                                name: 'pet',
                                in: 'header',
                                required: true,
                                schema: null,
                                content: [
                                    V31PartialHelper::createMediaType(
                                        mediaType: 'application/json',
                                        schema: V31PartialHelper::createSchema(
                                            allOf: [
                                                V31PartialHelper::createSchema(
                                                    type: 'integer'
                                                ),
                                                V31PartialHelper::createSchema(
                                                    type: 'number'
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        ]
                    ),
                    put: V31PartialHelper::createOperation(
                        operationId: 'second-put',
                        servers: [
                            V31PartialHelper::createServer(url: 'https://second-put.com')
                        ],
                        parameters: [
                            V31PartialHelper::createParameter(
                                name: 'user',
                                in: 'cookie',
                                required: false,
                                schema: V31PartialHelper::createSchema(
                                    type: 'object'
                                )
                            )
                        ]
                    )
                )
            ]
        ));
    }
}
