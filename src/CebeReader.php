<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

use cebe\{openapi as Cebe, openapi\exceptions as CebeException, openapi\spec as CebeSpec};
use Closure;
use Membrane\OpenAPIReader\Exception\{CannotRead, CannotSupport, InvalidOpenAPI};
use Membrane\OpenAPIReader\Factory\V30\FromCebe;
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

class CebeReader
{
    /** @param OpenAPIVersion[] $supportedVersions */
    public function __construct(
        private readonly array $supportedVersions,
    ) {
        if (empty($this->supportedVersions)) {
            throw CannotSupport::noSupportedVersions();
        }
        (fn (OpenAPIVersion ...$versions) => null)(...$this->supportedVersions);
    }

    public function readFromAbsoluteFilePath(string $absoluteFilePath, ?FileFormat $fileFormat = null): CebeSpec\OpenApi
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

        $this->validate($openAPI);

        return $openAPI;
    }

    public function readFromString(string $openAPI, FileFormat $fileFormat): CebeSpec\OpenApi
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

        $this->validate($openAPI);

        return $openAPI;
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

    private function validate(CebeSpec\OpenApi $openAPI): void
    {
        $this->isVersionSupported($openAPI->openapi) ?: throw CannotSupport::unsupportedVersion($openAPI->openapi);

        /** Currently only 3.0 Validated Objects exist */
        if (OpenAPIVersion::fromString($openAPI->openapi) === OpenAPIVersion::Version_3_0) {
            FromCebe::createOpenAPI($openAPI);
        }

        $openAPI->validate() ?: throw InvalidOpenAPI::failedCebeValidation(...$openAPI->getErrors());
    }

    private function isVersionSupported(string $version): bool
    {
        return in_array(OpenAPIVersion::fromString($version), $this->supportedVersions, true);
    }
}
