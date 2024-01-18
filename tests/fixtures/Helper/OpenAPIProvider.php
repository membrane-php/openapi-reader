<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures\Helper;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI;

final class OpenAPIProvider
{
    /**
     * This will return a "minimal" JSON string OpenAPI
     * Functions prefixed with "minimalV30" return equivalent OpenAPI
     */
    public static function minimalV30String(): string
    {
        $string = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'My Minimal OpenAPI', 'version' => '1.0.0'],
            'paths' => []
        ]);

        assert(is_string($string));

        return $string;
    }

    /**
     * This will return a "minimal" cebe library, OpenAPI object
     * Functions prefixed with "minimalV30" return equivalent OpenAPI
     */
    public static function minimalV30CebeObject(): Cebe\OpenApi
    {
        return new Cebe\OpenApi([
            'openapi' => '3.0.0',
            'info' => ['title' => 'My Minimal OpenAPI', 'version' => '1.0.0'],
            'paths' => []
        ]);
    }

    /**
     * This will return a "minimal" Membrane OpenAPI object
     * Functions prefixed with "minimalV30" return equivalent OpenAPI
     */
    public static function minimalV30MembraneObject(): OpenAPI
    {
        return new OpenAPI(PartialHelper::createOpenAPI(
            openapi: '3.0.0',
            title: 'My Minimal OpenAPI',
            version: '1.0.0',
            paths: []
        ));
    }

    /**
     * This will return a "detailed" JSON string OpenAPI
     * Functions prefixed with "detailedV30" return equivalent OpenAPI
     */
    public static function detailedV30String(): string
    {
        $string = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'My Detailed OpenAPI', 'version' => '1.0.1'],
            'servers' => [
                ['url' => 'https://server.net']
            ],
            'paths' => [
                '/first' => [
                    'parameters' => [
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer']
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
     * This will return a "detailed" cebe library, OpenAPI object
     * Functions prefixed with "detailedV30" return equivalent OpenAPI
     */
    public static function detailedV30CebeObject(): Cebe\OpenApi
    {
        return new Cebe\OpenApi([
            'openapi' => '3.0.0',
            'info' => ['title' => 'My Detailed OpenAPI', 'version' => '1.0.1'],
            'servers' => [
                ['url' => 'https://server.net']
            ],
            'paths' => [
                '/first' => [
                    'parameters' => [
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer']
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
    }

    /**
     * This will return a "detailed" Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI object
     * Functions prefixed with "detailedV30" return equivalent OpenAPI
     */
    public static function detailedV30MembraneObject(): OpenAPI
    {
        return new OpenAPI(PartialHelper::createOpenAPI(
            openapi: '3.0.0',
            title: 'My Detailed OpenAPI',
            version: '1.0.1',
            servers: [
                PartialHelper::createServer(url: 'https://server.net'),
            ],
            paths: [
                PartialHelper::createPathItem(
                    path: '/first',
                    parameters: [
                        PartialHelper::createParameter(
                            name: 'limit',
                            in: 'query',
                            required: false,
                            schema: PartialHelper::createSchema(
                                type: 'integer'
                            )
                        ),
                    ],
                    get: PartialHelper::createOperation(
                        operationId: 'first-get',
                        parameters: [
                            PartialHelper::createParameter(
                                name: 'pet',
                                in: 'header',
                                required: true,
                                schema: null,
                                content: [
                                    PartialHelper::createMediaType(
                                        mediaType: 'application/json',
                                        schema: PartialHelper::createSchema(
                                            allOf: [
                                                PartialHelper::createSchema(
                                                    type: 'integer'
                                                ),
                                                PartialHelper::createSchema(
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
                PartialHelper::createPathItem(
                    path: '/second',
                    servers: [
                        PartialHelper::createServer(
                            url: 'https://second-server.co.uk'
                        ),
                    ],
                    parameters: [
                        PartialHelper::createParameter(
                            name: 'limit',
                            in: 'query',
                            required: false,
                            schema: PartialHelper::createSchema(
                                type: 'integer'
                            )
                        ),
                    ],
                    get: PartialHelper::createOperation(
                        operationId: 'second-get',
                        parameters: [
                            PartialHelper::createParameter(
                                name: 'pet',
                                in: 'header',
                                required: true,
                                schema: null,
                                content: [
                                    PartialHelper::createMediaType(
                                        mediaType: 'application/json',
                                        schema: PartialHelper::createSchema(
                                            allOf: [
                                                PartialHelper::createSchema(
                                                    type: 'integer'
                                                ),
                                                PartialHelper::createSchema(
                                                    type: 'number'
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        ]
                    ),
                    put: PartialHelper::createOperation(
                        operationId: 'second-put',
                        servers: [
                            PartialHelper::createServer(url: 'https://second-put.com')
                        ],
                        parameters: [
                            PartialHelper::createParameter(
                                name: 'user',
                                in: 'cookie',
                                required: false,
                                schema: PartialHelper::createSchema(
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
