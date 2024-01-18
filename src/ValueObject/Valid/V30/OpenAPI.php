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
     * Optional, may be left empty.
     * If empty or unspecified, the array will contain the default Server.
     * The default Server has "url" === "/" and no "variables"
     * @var array<int, Server>
     */
    public readonly array $servers;

    /**
     * REQUIRED
     * It may be empty due to ACL constraints
     * The PathItem's relative endpoint key mapped to the PathItem
     * @var array<string,PathItem>
     */
    public readonly array $paths;

    public function __construct(Partial\OpenAPI $openAPI)
    {
        if (!isset($openAPI->title) || !isset($openAPI->version)) {
            throw InvalidOpenAPI::missingInfo();
        }

        parent::__construct(new Identifier("$openAPI->title($openAPI->version)"));

        if (!isset($openAPI->openAPI)) {
            throw InvalidOpenAPI::missingOpenAPIVersion($this->getIdentifier());
        }

        $this->servers = $this->validateServers(
            $this->getIdentifier(),
            $openAPI->servers
        );

        $this->paths = $this->validatePaths(
            $this->getIdentifier(),
            $openAPI->paths,
        );
    }

    /**
     * @param Partial\Server[] $servers
     * @return array<int,Server>>
     */
    private function validateServers(
        Identifier $identifier,
        array $servers
    ): array {
        if (empty($servers)) {
            $servers = [new Partial\Server('/')];
        }

        return array_values(
            array_map(fn($s) => new Server($identifier, $s), $servers)
        );
    }

        /**
     * @param null|Partial\PathItem[] $pathItems
     * @return array<string,PathItem>
     */
    private function validatePaths(
        Identifier $identifier,
        ?array $pathItems
    ): array {
        if (is_null($pathItems)) {
            throw InvalidOpenAPI::missingPaths($identifier);
        }

        if (empty($pathItems)) {
            $this->addWarning('No Paths in OpenAPI', Warning::EMPTY_PATHS);
        }

        $result = [];

        foreach ($pathItems as $pathItem) {
            if (!isset($pathItem->path)) {
                throw InvalidOpenAPI::pathMissingEndPoint($identifier);
            }

            if (!str_starts_with($pathItem->path, '/')) {
                throw InvalidOpenAPI::forwardSlashMustPrecedePath(
                    $identifier,
                    $pathItem->path
                );
            }
            if (isset($result[$pathItem->path])) {
                throw InvalidOpenAPI::identicalEndpoints(
                    $result[$pathItem->path]->getIdentifier()
                );
            }

            $result[$pathItem->path] = new PathItem(
                $identifier->append($pathItem->path),
                $this->servers,
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

        assert(is_string($pattern));

        return $pattern;
    }
}
