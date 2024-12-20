<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Server;
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
#[UsesClass(Server::class)]
#[UsesClass(Partial\Server::class)]
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
    #[Test, DataProvider('provideInvalidPartialObjects')]
    public function itCannotBeInvalid(
        InvalidOpenAPI $expected,
        Partial\OpenAPI $partialOpenAPI,
    ): void {
        self::expectExceptionObject($expected);

        OpenAPI::fromPartial($partialOpenAPI);
    }

    #[Test]
    #[TestDox('"paths" can be empty, but it does not leave much for Membrane to validate.')]
    public function itWarnsAgainstEmptyPaths(): void
    {
        $expected = [new Warning('No Paths in OpenAPI', Warning::EMPTY_PATHS)];
        $sut = OpenAPI::fromPartial(PartialHelper::createOpenAPI(paths: []));

        $actual = $sut->getWarnings()->findByWarningCode(Warning::EMPTY_PATHS);

        self::assertEquals($expected, $actual);
    }

    /**
     * @param Warning[] $expected
     */
    #[Test, DataProvider('provideDuplicateServers')]
    public function itWarnsAgainstDuplicateServers(
        array $expected,
        Partial\OpenAPI $openAPI,
    ): void {
        $sut = OpenAPI::fromPartial($openAPI);

        self::assertEquals(
            $expected,
            $sut
                ->getWarnings()
                ->findByWarningCode(Warning::IDENTICAL_SERVER_URLS)
        );
    }

    #[Test]
    public function itHasADefaultServer(): void
    {
        $title = 'My API';
        $version = '1.2.1';
        $sut = OpenAPI::fromPartial(PartialHelper::createOpenAPI(
            title: $title,
            version: $version,
            servers: [],
        ));

        $expected = [new Server(
            $sut->getIdentifier(),
            PartialHelper::createServer(url: '/')
        )];

        self::assertEquals($expected, $sut->servers);
    }

    #[Test]
    #[DataProvider('provideOpenAPIWithoutServers')]
    public function itCanCreateANewInstanceWithoutServers(
        array $servers,
        array $paths,
    ): void {
        $apiWith = fn($s) => OpenAPI::fromPartial(
            PartialHelper::createOpenAPI(
                servers: $s,
                paths: $paths,
            ),
        );

        self::assertEquals(
            $apiWith([new Partial\Server('/')]),
            $apiWith($servers)->withoutServers(),
        );
    }

    /**
     * @return Generator<array{
     *     0: InvalidOpenAPI,
     *     1: Partial\OpenAPI,
     * }>
     */
    public static function provideInvalidPartialObjects(): Generator
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

        yield 'no "paths" field' => $case(
            InvalidOpenAPI::missingPaths($identifier),
            ['paths' => null]
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
                        get: PartialHelper::createOperation(operationId: 'duplicate-id'),
                        post:    PartialHelper::createOperation(operationId: 'duplicate-id'),
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
                        get: PartialHelper::createOperation(operationId: 'duplicate-id')
                    ),
                    PartialHelper::createPathItem(
                        path: '/second',
                        get: PartialHelper::createOperation(operationId: 'duplicate-id'),
                    ),
                ]
            ]
        );
    }

    /**
     * @return Generator<array{
     *     0: Warning[],
     *     1: Partial\OpenAPI,
     * }>
     */
    public static function provideDuplicateServers(): Generator
    {
        $expectedWarning = new Warning(
            'Server URLs are not unique',
            Warning::IDENTICAL_SERVER_URLS
        );

        $case = fn($servers) => [
            [$expectedWarning],
            PartialHelper::createOpenAPI(servers: $servers),
        ];

        yield 'Completely identical: "/"' => $case([
            PartialHelper::createServer('/'),
            PartialHelper::createServer('/'),
        ]);

        yield 'Completely identical: "https://www.server.net"' => $case([
            PartialHelper::createServer('https://www.server.net'),
            PartialHelper::createServer('https://www.server.net'),
        ]);

        yield 'Identical IF you ignore trailing forward slashes' => $case([
            PartialHelper::createServer(''),
            PartialHelper::createServer('/'),
        ]);
    }

    public static function provideOpenAPIWithoutServers(): Generator
    {
        yield 'default server' => [
            [new Partial\Server('/')],
            [],
        ];

        yield 'static server' => [
            [new Partial\Server('hello-world.net/')],
            [],
        ];

        yield 'multiple servers' => [
            [
                new Partial\Server('hello-world.net/'),
                new Partial\Server('howdy-planet.io/'),
            ],
            [],
        ];

        yield 'dynamic server' => [
            [new Partial\Server('hello-{world}.net/', [
                new Partial\ServerVariable('world', 'world'),
            ])],
            [],
        ];

        yield 'path item' => [
            [new Partial\Server('hello-parameter.io/')],
            [PartialHelper::createPathItem()]
        ];
    }
}
