<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class OpenAPI extends Validated
{
    /**
     * The PathItem's relative endpoint key mapped to the PathItem
     * @var array<string,PathItem>
     */
    private readonly array $paths;

    public function __construct(Partial\OpenAPI $openAPI)
    {
        if (!isset($openAPI->title) || !isset($openAPI->version)) {
            throw InvalidOpenAPI::missingInfo();
        }

        parent::__construct(new Identifier("$openAPI->title($openAPI->version)"));

        if (!isset($openAPI->openAPI)) {
            throw InvalidOpenAPI::missingOpenAPIVersion($this->getIdentifier());
        }

        if (empty($openAPI->paths)) {
            $this->addWarning('No Paths in OpenAPI', Warning::EMPTY_PATHS);
        }

        $this->paths = $this->getPaths($openAPI->paths);
    }

    /**
     * @param Partial\PathItem[] $pathItems
     * @return array<string,PathItem>
     */
    private function getPaths(array $pathItems): array
    {
        $result = [];
        foreach ($pathItems as $pathItem) {
            if (!isset($pathItem->path)) {
                throw InvalidOpenAPI::pathMissingEndPoint($this->getIdentifier());
            }

            if (!str_starts_with($pathItem->path, '/')) {
                throw InvalidOpenAPI::forwardSlashMustPrecedePath(
                    $this->getIdentifier(),
                    $pathItem->path
                );
            }
            if (isset($result[$pathItem->path])) {
                throw InvalidOpenAPI::identicalEndpoints(
                    $result[$pathItem->path]->getIdentifier()
                );
            }

            $result[$pathItem->path] = new PathItem(
                $this->getIdentifier()->append($pathItem->path),
                $pathItem
            );
        }

        $this->checkForEquivalentPathTemplates($result);
        $this->checkForDuplicatedOperationIds($result);

        return $result;
    }

    /**
     * @param array<string,PathItem> $pathItems
     */
    private function checkForEquivalentPathTemplates(array $pathItems): void
    {
        $regexToIdentifier = [];
        foreach ($pathItems as $path => $pathItem) {
            $regex = $this->getPathRegex($path);

            if (isset($regexToIdentifier[$regex])) {
                throw InvalidOpenAPI::equivalentTemplates(
                    $regexToIdentifier[$regex],
                    $pathItem->getIdentifier()
                );
            }

            $regexToIdentifier[$regex] = $pathItem->getIdentifier();
        }
    }

    /**
     * @param PathItem[] $paths
     */
    private function checkForDuplicatedOperationIds(array $paths): void
    {
        $checked = [];

        foreach ($paths as $path => $pathItem) {
            foreach ($pathItem->getOperations() as $method => $operation) {
                $id = $operation->operationId;

                if (isset($checked[$id])) {
                    throw InvalidOpenAPI::duplicateOperationIds(
                        $id,
                        $checked[$id][0],
                        $checked[$id][1],
                        $path,
                        $method,
                    );
                }

                $checked[$id] = [$path, $method];
            }
        }
    }

    private function getPathRegex(string $path): string
    {
        $pattern = preg_replace('#{[^/]+}#', '{([^/]+)}', $path);

        if (!is_string($pattern)) {
            throw InvalidOpenAPI::malformedUrl($this->getIdentifier(), $path);
        }

        return $pattern;
    }
}
