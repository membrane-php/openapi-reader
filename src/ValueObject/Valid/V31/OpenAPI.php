<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class OpenAPI extends Validated
{
    /**
     * @param array<int, Server> $servers
     * Optional, may be left empty.
     * If empty or unspecified, the array will contain the default Server.
     * The default Server has "url" === "/" and no "variables"
     *
     * @param array<string,PathItem> $paths
     * REQUIRED:
     * It may be empty due to ACL constraints
     * The PathItem's relative endpoint key mapped to the PathItem
     */
    private function __construct(
        Identifier $identifier,
        public readonly array $servers,
        public readonly array $paths
    ) {
        parent::__construct($identifier);

        $this->reviewServers($this->servers);
        $this->reviewPaths($this->paths);
    }

    public function withoutServers(): OpenAPI
    {
        return new OpenAPI(
            $this->getIdentifier(),
            [new Server($this->getIdentifier(), new Partial\Server('/'))],
            array_map(fn($p) => $p->withoutServers(), $this->paths),
        );
    }

    public static function fromPartial(Partial\OpenAPI $openAPI): self
    {
        $identifier = new Identifier(sprintf(
            '%s(%s)',
            $openAPI->title ?? throw InvalidOpenAPI::missingInfo(),
            $openAPI->version ?? throw InvalidOpenAPI::missingInfo(),
        ));

        $openAPI->openAPI ??
            throw InvalidOpenAPI::missingOpenAPIVersion($identifier);

        $servers = self::validateServers($identifier, $openAPI->servers);
        $paths = self::validatePaths($identifier, $servers, $openAPI->paths);

        return new OpenAPI($identifier, $servers, $paths);
    }

    /**
     * @param Partial\Server[] $servers
     * @return array<int,Server>
     */
    private static function validateServers(
        Identifier $identifier,
        array $servers
    ): array {
        if (empty($servers)) {
            return [new Server($identifier, new Partial\Server('/'))];
        }

        return array_values(array_map(
            fn($s) => new Server($identifier, $s),
            $servers
        ));
    }

    /**
     * @param Server[] $servers
     */
    private function reviewServers(array $servers): void
    {
        $uniqueURLS = array_unique(array_map(fn($s) => $s->url, $servers));
        if (count($servers) !== count($uniqueURLS)) {
            $this->addWarning(
                'Server URLs are not unique',
                Warning::IDENTICAL_SERVER_URLS
            );
        }
    }

    /**
     * @param Server[] $servers
     * @param null|Partial\PathItem[] $pathItems
     * @return array<string,PathItem>
     */
    private static function validatePaths(
        Identifier $identifier,
        array $servers,
        ?array $pathItems
    ): array {
        if (is_null($pathItems)) {
            throw InvalidOpenAPI::missingPaths($identifier);
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

            $result[$pathItem->path] = PathItem::fromPartial(
                $identifier->append($pathItem->path),
                $servers,
                $pathItem
            );
        }

        self::checkForEquivalentPathTemplates($result);
        self::checkForDuplicatedOperationIds($result);

        return $result;
    }

    /**
     * @param PathItem[] $paths
     */
    private function reviewPaths(array $paths): void
    {
        if (empty($paths)) {
            $this->addWarning('No Paths in OpenAPI', Warning::EMPTY_PATHS);
        }
    }

    /**
     * @param array<string,PathItem> $pathItems
     */
    private static function checkForEquivalentPathTemplates(array $pathItems): void
    {
        $regexToIdentifier = [];
        foreach ($pathItems as $path => $pathItem) {
            $regex = self::getPathRegex($path);

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
    private static function checkForDuplicatedOperationIds(array $paths): void
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

    private static function getPathRegex(string $path): string
    {
        $pattern = preg_replace('#{[^/]+}#', '{([^/]+)}', $path);

        assert(is_string($pattern));

        return $pattern;
    }
}
