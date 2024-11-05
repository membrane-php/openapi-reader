<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

use cebe\{openapi as Cebe, openapi\exceptions as CebeException, openapi\spec as CebeSpec};
use Closure;
use Membrane\OpenAPIReader\Exception\{CannotRead, CannotSupport, InvalidOpenAPI};
use Membrane\OpenAPIReader\ValueObject\Valid;
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

final class MembraneReader
{
    /** @param OpenAPIVersion[] $supportedVersions */
    public function __construct(
        private readonly array $supportedVersions,
    ) {
        if (empty($this->supportedVersions)) {
            throw CannotSupport::noSupportedVersions();
        }
    }

    public function readFromAbsoluteFilePath(string $absoluteFilePath, ?FileFormat $fileFormat = null): Valid\V30\OpenAPI|Valid\V31\OpenAPI
    {
        file_exists($absoluteFilePath) ?: throw CannotRead::fileNotFound($absoluteFilePath);

        $fileFormat ??= FileFormat::fromFileExtension(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));

        try {
            $openAPI = $this->getCebeObject(match ($fileFormat) {
                FileFormat::Json => fn() => Cebe\Reader::readFromJsonFile($absoluteFilePath),
                FileFormat::Yaml => fn() => Cebe\Reader::readFromYamlFile($absoluteFilePath),
                default => throw CannotRead::unrecognizedFileFormat($absoluteFilePath)
            });
        } catch (CebeException\UnresolvableReferenceException $e) {
            throw CannotRead::unresolvedReference($e);
        }

        return $this->getValidatedObject($openAPI);
    }

    public function readFromString(string $openAPI, FileFormat $fileFormat): Valid\V30\OpenAPI|Valid\V31\OpenAPI
    {
        if (preg_match('#\s*[\'\"]?\$ref[\'\"]?\s*:\s*[\'\"]?[^\s\'\"\#]#', $openAPI)) {
            throw CannotRead::cannotResolveExternalReferencesFromString();
        }

        $openAPI = $this->getCebeObject(match ($fileFormat) {
            FileFormat::Json => fn() => Cebe\Reader::readFromJson($openAPI),
            FileFormat::Yaml => fn() => Cebe\Reader::readFromYaml($openAPI),
        });

        try {
            $openAPI->resolveReferences(new Cebe\ReferenceContext($openAPI, '/tmp'));
        } catch (CebeException\UnresolvableReferenceException $e) {
            throw CannotRead::unresolvedReference($e);
        }

        return $this->getValidatedObject($openAPI);
    }

    /** @param Closure():CebeSpec\OpenApi $readOpenAPI */
    private function getCebeObject(Closure $readOpenAPI): CebeSpec\OpenApi
    {
        try {
            return $readOpenAPI();
        } catch (TypeError | CebeException\TypeErrorException | ParseException $e) {
            throw CannotRead::invalidFormatting($e);
        }
    }

    private function getValidatedObject(CebeSpec\OpenApi $openAPI): Valid\V30\OpenAPI|Valid\V31\OpenAPI
    {
        $version = OpenAPIVersion::fromString($openAPI->openapi) ??
            throw CannotSupport::unsupportedVersion($openAPI->openapi);

        $this->isVersionSupported($version) ?:
            throw CannotSupport::unsupportedVersion($openAPI->openapi);

        $validatedObject = match($version) {
            OpenAPIVersion::Version_3_0 => Factory\V30\FromCebe::createOpenAPI($openAPI),
            OpenAPIVersion::Version_3_1 => Factory\V31\FromCebe::createOpenAPI($openAPI),
        };

        $openAPI->validate() ?: throw InvalidOpenAPI::failedCebeValidation(...$openAPI->getErrors());

        return $validatedObject;
    }

    private function isVersionSupported(OpenAPIVersion $version): bool
    {
        return in_array($version, $this->supportedVersions, true);
    }
}
