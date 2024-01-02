<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Factory\V30;

use cebe\openapi\spec as Cebe;
use Generator;
use Membrane\OpenAPIReader\Factory\V30\FromCebe;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\MediaType;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FromCebe::class)]
#[UsesClass(OpenAPI::class)]
#[UsesClass(Partial\OpenAPI::class)]
#[UsesClass(PathItem::class)]
#[UsesClass(Partial\PathItem::class)]
#[UsesClass(Operation::class)]
#[UsesClass(Partial\Operation::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Partial\Parameter::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Partial\MediaType::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Method::class)]
class FromCebeTest extends TestCase
{
    #[Test, DataProvider('provideCebeOpenAPIObjects')]
    public function itConstructsValidOpenAPIObjects(
        OpenAPI $expected,
        Cebe\OpenApi $openApi,
    ): void {
        self::assertEquals($expected, FromCebe::createOpenAPI($openApi));
    }

    public static function provideCebeOpenAPIObjects(): Generator
    {
        yield 'minimal OpenAPI' => [
            new OpenAPI(PartialHelper::createOpenAPI(
                openapi: '3.0.0',
                title: 'Test API',
                version: '1.0.1',
                paths: []
            )),
            new Cebe\OpenApi([
                'openapi' => '3.0.0',
                'info' => ['title' => 'Test API', 'version' => '1.0.1'],
                'paths' => [],
            ])
        ];

        yield 'detailed OpenAPI' => [
            new OpenAPI(PartialHelper::createOpenAPI(
                openapi: '3.0.0',
                title: 'Test API',
                version: '1.0.1',
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
                        operations: [
                            PartialHelper::createOperation(
                                operationId: 'test-id',
                                method: 'get',
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
                        ]
                    )
                ]
            )),
            new Cebe\OpenApi([
                'openapi' => '3.0.0',
                'info' => ['title' => 'Test API', 'version' => '1.0.1'],
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
                            'operationId' => 'test-id',
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
                            ]
                        ]
                    ]
                ],
            ])
        ];
    }
}
