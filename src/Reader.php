<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

use cebe\{openapi as Cebe, openapi\exceptions as CebeException, openapi\spec as CebeSpec};
use Membrane\OpenAPIReader\Exception\{CannotRead, CannotSupport, InvalidOpenAPI};
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

final class Reader
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
            $openAPI = match ($fileFormat) {
                FileFormat::Json => Cebe\Reader::readFromJsonFile($absoluteFilePath),
                FileFormat::Yaml => Cebe\Reader::readFromYamlFile($absoluteFilePath),
                default => throw CannotRead::unrecognizedFileFormat($absoluteFilePath)
            };
        } catch (TypeError | CebeException\TypeErrorException | ParseException $e) {
            throw CannotRead::invalidFormatting($e);
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

        try {
            $openAPI = match ($fileFormat) {
                FileFormat::Json => Cebe\Reader::readFromJson($openAPI),
                FileFormat::Yaml => Cebe\Reader::readFromYaml($openAPI),
            };
        } catch (TypeError | CebeException\TypeErrorException | ParseException $e) {
            throw CannotRead::invalidFormatting($e);
        }

        $openAPI->resolveReferences(new Cebe\ReferenceContext($openAPI, '/tmp'));

        $this->validate($openAPI);

        return $openAPI;
    }

    private function validate(CebeSpec\OpenApi $openAPI): void
    {
        $this->isVersionSupported($openAPI->openapi) ?: throw CannotSupport::unsupportedVersion($openAPI->openapi);

        $existingOperationIds = [];

        // OpenAPI Version 3.1 does not require paths
        if (isset($openAPI->paths)) {
            foreach ($openAPI->paths as $pathUrl => $path) {
                $this->parametersContainSchemaXorContent($path);

                foreach ($path->getOperations() as $method => $operation) {
                    $this->parametersContainSchemaXorContent($operation);

                    Method::tryFrom($method) !== null ?: throw CannotSupport::unsupportedMethod($pathUrl, $method);

                    isset($operation->operationId) ?: throw CannotSupport::missingOperationId($pathUrl, $method);

                    if (isset($existingOperationIds[$operation->operationId])) {
                        throw InvalidOpenAPI::duplicateOperationIds(
                            $operation->operationId,
                            $existingOperationIds[$operation->operationId]['path'],
                            $existingOperationIds[$operation->operationId]['method'],
                            $pathUrl,
                            $method
                        );
                    }
                    $existingOperationIds[$operation->operationId] = ['path' => $pathUrl, 'method' => $method];
                }
            }
        }

        $openAPI->validate() ?: throw InvalidOpenAPI::failedCebeValidation(...$openAPI->getErrors());
    }

    private function isVersionSupported(string $version): bool
    {
        return in_array(OpenAPIVersion::fromString($version), $this->supportedVersions, true);
    }

    private function parametersContainSchemaXorContent(CebeSpec\PathItem | CebeSpec\Operation $specObject): void
    {
        foreach ($specObject->parameters as $parameter) {
            assert($parameter instanceof CebeSpec\Parameter);

            $result = isset($parameter->schema);

            if (!empty($parameter->content)) {
                $result = !$result;
            }

            if (!$result) {
                throw InvalidOpenAPI::mustHaveSchemaXorContent($parameter->name);
            }
        }
    }
}
