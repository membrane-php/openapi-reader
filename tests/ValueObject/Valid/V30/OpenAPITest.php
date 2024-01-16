<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPI::class)]
#[CoversClass(Partial\OpenAPI::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(PathItem::class)]
#[UsesClass(Partial\PathItem::class)]
#[UsesClass(Operation::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
#[UsesClass(Method::class)]
class OpenAPITest extends TestCase
{
    #[Test, DataProvider('providePartialOpenAPIs')]
    public function itValidatesOpenAPIObjects(
        InvalidOpenAPI $expected,
        Partial\OpenAPI $partialOpenAPI,
    ): void {
        self::expectExceptionObject($expected);

        new OpenAPI($partialOpenAPI);
    }

    #[Test]
    #[TestDox('no "paths" is technically valid, but it does not leave much for Membrane to validate.')]
    public function itWarnsAgainstEmptyPaths(): void
    {
        $expected = new Warning('No Paths in OpenAPI', Warning::EMPTY_PATHS);
        $title = 'My API';
        $version = '1.2.1';
        $sut = new OpenAPI(PartialHelper::createOpenAPI(
            title: $title,
            version: $version,
            paths: [],
        ));

        self::assertEquals($expected, $sut->getWarnings()->all()[0]);
    }

    public static function providePartialOpenAPIs(): Generator
    {
        $title = 'Test OpenAPI';
        $version = '1.0.0';
        $identifier = new Identifier("$title($version)");

        $case = fn($expected, $data) => [
            $expected,
            PartialHelper::createOpenAPI(...[
                'title' => $title,
                'version' => $version,
                ...$data
            ])
        ];

        yield 'no "openapi" field' => $case(
            InvalidOpenAPI::missingOpenAPIVersion($identifier),
            ['openapi' => null]
        );

        yield 'no "title" field on Info object' => $case(
            InvalidOpenAPI::missingInfo(),
            ['title' => null]
        );

        yield 'no "version" field on Info object' => $case(
            InvalidOpenAPI::missingInfo(),
            ['version' => null]
        );

        yield 'path without an endpoint' => $case(
            InvalidOpenAPI::pathMissingEndPoint($identifier),
            ['paths' => [PartialHelper::createPathItem(path: null)]]
        );

        yield 'path endpoint is not preceded by a forward slash ' => $case(
            InvalidOpenAPI::forwardSlashMustPrecedePath($identifier, 'path'),
            ['paths' => [PartialHelper::createPathItem(path: 'path')]]
        );

        yield 'two paths with identical endpoints' => $case(
            InvalidOpenAPI::identicalEndpoints($identifier->append('/path')),
            [
                'paths' => [
                    PartialHelper::createPathItem(path: '/path'),
                    PartialHelper::createPathItem(path: '/path')
                ]
            ]
        );

        yield 'two paths with equivalent endpoint templates' => $case(
            InvalidOpenAPI::equivalentTemplates(
                $identifier->append('/path/{param1}'),
                $identifier->append('/path/{param2}')
            ),
            [
                'paths' => [
                    PartialHelper::createPathItem(path: '/path/{param1}'),
                    PartialHelper::createPathItem(path: '/path/{param2}'),
                ]
            ]
        );

        yield 'one path with two identical operationIds' => $case(
            InvalidOpenAPI::duplicateOperationIds('duplicate-id', '/path', 'get', '/path', 'post'),
            [
                'paths' => [
                    PartialHelper::createPathItem(
                        path: '/path',
                        operations: [
                            PartialHelper::createOperation(operationId: 'duplicate-id', method: 'get'),
                            PartialHelper::createOperation(operationId: 'duplicate-id', method: 'post')
                        ],
                    )
                ]
            ]
        );

        yield 'two path with identical operationIds' => $case(
            InvalidOpenAPI::duplicateOperationIds('duplicate-id', '/first', 'get', '/second', 'get'),
            [
                'paths' => [
                    PartialHelper::createPathItem(
                        path: '/first',
                        operations: [
                            PartialHelper::createOperation(operationId: 'duplicate-id', method: 'get'),
                        ],
                    ),
                    PartialHelper::createPathItem(
                        path: '/second',
                        operations: [
                            PartialHelper::createOperation(operationId: 'duplicate-id', method: 'get'),
                        ],
                    ),
                ]
            ]
        );
    }
}
