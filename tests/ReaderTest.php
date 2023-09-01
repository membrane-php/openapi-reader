<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests;

use cebe\{openapi as Cebe, openapi\exceptions as CebeException, openapi\spec as CebeSpec};
use Generator;
use Membrane\OpenAPIReader\Exception\{CannotRead, CannotSupport, InvalidOpenAPI};
use Membrane\OpenAPIReader\{FileFormat, Method, OpenAPIVersion};
use Membrane\OpenAPIReader\Reader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Reader::class)]
#[CoversClass(CannotRead::class), CoversClass(CannotSupport::class), CoversClass(InvalidOpenAPI::class)]
#[UsesClass(FileFormat::class), UsesClass(Method::class), UsesClass(OpenAPIVersion::class)]
class ReaderTest extends TestCase
{
    private string $petstorePath = __DIR__ . '/fixtures/petstore.yaml';

    #[Test]
    public function itMustSupportAtleastOneOpenAPIVersion(): void
    {
        self::expectExceptionObject(CannotSupport::noSupportedVersions());

        new Reader([]);
    }

    #[Test]
    public function itMustHaveAnArrayContainingOnlyOpenAPIVersions(): void
    {
        self::expectException(TypeError::class);

        new Reader(['3.0.0']);
    }

    #[Test]
    public function itCannotReadFilesItCannotFind(): void
    {
        $filePath = vfsStream::setup()->url() . '/open-api';

        self::assertFalse(file_exists($filePath));

        self::expectExceptionObject(CannotRead::fileNotFound($filePath));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($filePath, FileFormat::Json);
    }

    #[Test, TestDox('It cannot resolve references from relative filepaths')]
    public function itCannotReadFromRelativeFilePaths(): void
    {
        $filePath = './tests/fixtures/petstore.yaml';

        self::assertTrue(file_exists($filePath));

        self::expectExceptionObject(
            CannotRead::unresolvedReference(new CebeException\UnresolvableReferenceException())
        );

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($filePath);
    }

    public static function provideMinimalOpenAPIAsArray(): Generator
    {
        yield [['openapi' => '3.0.0', 'info' => ['title' => '', 'version' => '1.0.0'], 'paths' => []]];
    }

    #[Test]
    #[DataProvider('provideMinimalOpenAPIAsArray')]
    public function itCannotSupportUnspecifiedOpenAPIVersions(array $openAPIArray): void
    {
        self::expectExceptionObject(CannotSupport::unsupportedVersion($openAPIArray['openapi']));

        (new Reader([OpenAPIVersion::Version_3_1]))
            ->readFromString(json_encode($openAPIArray), FileFormat::Json);
    }

    #[Test]
    #[DataProvider('provideMinimalOpenAPIAsArray')]
    public function itCanSupportSpecifiedOpenAPIVersions(array $openAPIArray): void
    {
        $openAPIObject = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString(json_encode($openAPIArray), FileFormat::Json);

        self::assertInstanceOf(CebeSpec\OpenApi::class, $openAPIObject);
    }

    #[Test]
    public function itCannotSupportUnrecognizedFileFormats(): void
    {
        self::assertNull(FileFormat::fromFileExtension(pathinfo(__FILE__, PATHINFO_EXTENSION)));

        self::expectExceptionObject(CannotRead::unrecognizedFileFormat(__FILE__));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath(__FILE__);
    }

    public static function provideInvalidFormatting(): Generator
    {
        yield 'Empty string to be interpreted as json' => ['', FileFormat::Json];
        yield 'Empty string to be interpreted as yaml' => ['', FileFormat::Yaml];
        yield 'Invalid json format' => ['{openapi: ",', FileFormat::Json];
        yield 'Invalid yaml format' => ['---openapi: ",- title: "invalid"', FileFormat::Yaml];
    }

    #[Test]
    #[DataProvider('provideInvalidFormatting')]
    public function itCannotReadInvalidFormattingFromAbsoluteFilePaths(
        string $openAPIString,
        FileFormat $fileFormat
    ): void {
        $filePath = vfsStream::setup()->url() . '/api';
        file_put_contents($filePath, $openAPIString);

        self::expectExceptionObject(CannotRead::invalidFormatting(new TypeError()));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($filePath, $fileFormat);
    }

    #[Test]
    #[DataProvider('provideInvalidFormatting')]
    public function itCannotReadInvalidFormattingFromString(
        string $openAPIString,
        FileFormat $fileFormat
    ): void {
        self::expectExceptionObject(CannotRead::invalidFormatting(new TypeError()));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString($openAPIString, $fileFormat);
    }

    public static function provideInvalidOpenAPIs(): Generator
    {
        yield 'Invalid OpenAPI in valid json format' => (function () {
            $jsonOpenAPIString = json_encode(['openapi' => '3.0.0']);
            return [
                $jsonOpenAPIString,
                InvalidOpenAPI::failedCebeValidation(...Cebe\Reader::readFromJson($jsonOpenAPIString)->getErrors()),
            ];
        })();

        $openAPI = ['openapi' => '3.0.0', 'info' => ['title' => '', 'version' => '1.0.0'], 'paths' => []];
        $openAPIPath = fn($operationId) => [
            'operationId' => $operationId,
            'responses' => [200 => ['description' => ' Successful Response']],
        ];

        yield 'duplicate operationIds on the same path' => [
            (function () use ($openAPI, $openAPIPath) {
                $openAPIArray = $openAPI;
                $openAPIArray['paths']['/path'] = [
                    'get' => $openAPIPath('duplicate-id'),
                    'post' => $openAPIPath('duplicate-id'),
                ];
                return json_encode($openAPIArray);
            })(),
            InvalidOpenAPI::duplicateOperationIds('duplicate-id', '/path', 'get', '/path', 'post'),
        ];

        yield 'duplicate operationIds on separate paths' => [
            (function () use ($openAPI, $openAPIPath) {
                $openAPIArray = $openAPI;
                $openAPIArray['paths'] = [
                    '/firstpath' => ['get' => $openAPIPath('duplicate-id')],
                    '/secondpath' => ['get' => $openAPIPath('duplicate-id')],
                ];
                return json_encode($openAPIArray);
            })(),
            InvalidOpenAPI::duplicateOperationIds('duplicate-id', '/firstpath', 'get', '/secondpath', 'get'),
        ];
    }

    #[Test]
    #[DataProvider('provideInvalidOpenAPIs')]
    public function itWillNotProcessInvalidOpenAPI(string $openAPIString, InvalidOpenAPI $expectedException): void
    {
        self::expectExceptionObject($expectedException);

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString($openAPIString, FileFormat::Json);
    }

    public static function provideUnsupportedMethods(): Generator
    {
        yield 'HEAD' => ['head'];
        yield 'OPTIONS' => ['options'];
        yield 'TRACE' => ['trace'];
    }

    #[Test, TestDox('It only supports cases of the Method Enum')]
    #[DataProvider('provideUnsupportedMethods')]
    public function itCannotSupportUnsupportedMethods(string $method): void
    {
        self::assertNull(Method::tryFrom($method));

        $openAPIString = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => '', 'version' => '1.0.0'],
            'paths' => [
                '/path' => [
                    $method => [
                        'operationId' => 'test-id',
                        'responses' => [200 => ['description' => ' Successful Response']],
                    ],
                ],
            ],
        ]);

        self::expectExceptionObject(CannotSupport::unsupportedMethod('/path', $method));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString($openAPIString, FileFormat::Json);
    }

    #[Test, TestDox('Membrane requires operationIds for caching and routing')]
    public function itCannotSupportMissingOperationIds(): void
    {
        $openAPIString = json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => '', 'version' => '1.0.0'],
            'paths' => [
                '/path' => [
                    'get' => [
                        // Missing operationId here
                        'responses' => [200 => ['description' => ' Successful Response']],
                    ],
                ],
            ],
        ]);

        self::expectExceptionObject(CannotSupport::missingOperationId('/path', 'get'));

        (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString($openAPIString, FileFormat::Json);
    }

    #[Test]
    public function itResolvesInternalReferencesFromAbsoluteFilePath(): void
    {
        $api = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($this->petstorePath, FileFormat::Yaml);

        self::assertInstanceOf(
            CebeSpec\Schema::class,
            $api->paths['/pets']->get->responses[200]->content['application/json']->schema
        );
    }

    #[Test]
    public function itResolvesInternalReferencesFromString(): void
    {
        $api = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString(file_get_contents($this->petstorePath), FileFormat::Yaml);

        self::assertInstanceOf(
            CebeSpec\Schema::class,
            $api->paths['/pets']->get->responses[200]->content['application/json']->schema
        );
    }

    public static function provideOpenAPIWithExternalReference(): Generator
    {
        yield (function () {
            $externalRef = 'schema.json';
            return [
                json_encode([
                    'openapi' => '3.0.0',
                    'info' => ['title' => 'API With Reference Object', 'version' => '1.0.0'],
                    'paths' => [
                        '/path' => [
                            'get' => [
                                'operationId' => 'get-path',
                                'responses' => [
                                    200 => [
                                        'description' => 'Successful Response',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => $externalRef,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                $externalRef,
            ];
        })();
    }

    #[Test]
    #[DataProvider('provideOpenAPIWithExternalReference')]
    public function itResolvesExternalReferencesFromAbsoluteFilePath(
        string $openAPIString,
        string $externalReference
    ): void {
        vfsStream::setup();
        file_put_contents(vfsStream::url('root/openapi.json'), $openAPIString);
        file_put_contents(vfsStream::url('root/' . $externalReference), '{"type":"integer"}');

        $openAPIObject = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath(vfsStream::url('root/openapi.json'));

        self::assertInstanceOf(
            CebeSpec\Schema::class,
            $openAPIObject->paths['/path']->get->responses[200]->content['application/json']->schema
        );
    }

    #[Test]
    #[DataProvider('provideOpenAPIWithExternalReference')]
    public function itCannotResolveExternalReferenceFromString(string $openAPIString,): void
    {
        self::expectException(CannotRead::class);

        // This call will trigger a file_not_found E_WARNING
        @(new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromString($openAPIString, FileFormat::Json);
    }
}
