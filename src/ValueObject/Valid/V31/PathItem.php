<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class PathItem extends Validated
{
    /**
     * @param array<int,Server> $servers
     *
     * @param  array<int,Parameter> $parameters
     * The list MUST NOT include duplicated parameters.
     * A unique parameter is defined by a combination of a name and location.
     */
    private function __construct(
        Identifier $identifier,
        public readonly array $servers,
        public readonly array $parameters,
        public readonly ?Operation $get,
        public readonly ?Operation $put,
        public readonly ?Operation $post,
        public readonly ?Operation $delete,
        public readonly ?Operation $options,
        public readonly ?Operation $head,
        public readonly ?Operation $patch,
        public readonly ?Operation $trace,
    ) {
        parent::__construct($identifier);

        $this->reviewServers($servers);
        $this->reviewParameters($parameters);
        $this->reviewOperations($this->getOperations());
    }

    public function withoutServers(): PathItem
    {
        return new PathItem(
            $this->getIdentifier(),
            [new Server(
                new Identifier($this->getIdentifier()->fromStart() ?? ''),
                new Partial\Server('/')
            ),
            ],
            $this->parameters,
            $this->get?->withoutServers(),
            $this->put?->withoutServers(),
            $this->post?->withoutServers(),
            $this->delete?->withoutServers(),
            $this->options?->withoutServers(),
            $this->head?->withoutServers(),
            $this->patch?->withoutServers(),
            $this->trace?->withoutServers(),
        );
    }

    /**
     * @param array<int,Server> $openAPIServers
     * If the pathItem does not contain servers, this will be used instead
     */
    public static function fromPartial(
        Identifier $identifier,
        array $openAPIServers,
        Partial\PathItem $pathItem,
    ): PathItem {
        $servers = self::validateServers(
            $identifier,
            $openAPIServers,
            $pathItem->servers,
        );

        $parameters = self::validateParameters(
            $identifier,
            $pathItem->parameters
        );

        return new PathItem(
            identifier: $identifier,
            servers: $servers,
            parameters: $parameters,
            get: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::GET,
                $pathItem->get
            ),
            put: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::PUT,
                $pathItem->put
            ),
            post: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::POST,
                $pathItem->post
            ),
            delete: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::DELETE,
                $pathItem->delete
            ),
            options: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::OPTIONS,
                $pathItem->options
            ),
            head: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::HEAD,
                $pathItem->head
            ),
            patch: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::PATCH,
                $pathItem->patch
            ),
            trace: self::validateOperation(
                $identifier,
                $servers,
                $parameters,
                Method::TRACE,
                $pathItem->trace
            ),
        );
    }

    /**
     * Operation "method" keys mapped to Operation values
     * @return array<string,Operation>
     */
    public function getOperations(): array
    {
        return array_filter(
            [
                Method::GET->value => $this->get,
                Method::PUT->value => $this->put,
                Method::POST->value => $this->post,
                Method::DELETE->value => $this->delete,
                Method::OPTIONS->value => $this->options,
                Method::HEAD->value => $this->head,
                Method::PATCH->value => $this->patch,
                Method::TRACE->value => $this->trace,
            ],
            fn($o) => !is_null($o)
        );
    }

    /**
     * @param array<int,Server> $openAPIServers
     * @param Partial\Server[] $pathServers
     * @return array<int,Server>>
     */
    private static function validateServers(
        Identifier $identifier,
        array $openAPIServers,
        array $pathServers
    ): array {
        if (empty($pathServers)) {
            return $openAPIServers;
        }

        return array_values(array_map(
            fn($s) => new Server($identifier, $s),
            $pathServers
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
     * @param Partial\Parameter[] $parameters
     * @return array<int,Parameter>
     */
    private static function validateParameters(
        Identifier $identifier,
        array $parameters
    ): array {
        $result = array_values(array_map(
            fn($p) => new Parameter($identifier, $p),
            $parameters
        ));

        foreach ($result as $index => $parameter) {
            foreach (array_slice($result, $index + 1) as $otherParameter) {
                if ($parameter->isIdentical($otherParameter)) {
                    throw InvalidOpenAPI::duplicateParameters(
                        $identifier,
                        $parameter->getIdentifier(),
                        $otherParameter->getIdentifier(),
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array<int,Parameter> $parameters
     */
    private function reviewParameters(array $parameters): void
    {
        foreach ($parameters as $index => $parameter) {
            foreach (array_slice($parameters, $index + 1) as $other) {
                if ($parameter->isSimilar($other)) {
                    $this->addWarning(
                        <<<TEXT
                        'This contains confusingly similar parameter names:
                         $parameter->name
                         $other->name
                        TEXT,
                        Warning::SIMILAR_NAMES
                    );
                }
            }
        }
    }

    /**
     * @param Server[] $servers
     * @param Parameter[] $parameters
     */
    private static function validateOperation(
        Identifier $identifier,
        array $servers,
        array $parameters,
        Method $method,
        ?Partial\Operation $operation
    ): ?Operation {
        if (is_null($operation)) {
            return null;
        }

        return Operation::fromPartial(
            $identifier,
            $servers,
            $parameters,
            $method,
            $operation,
        );
    }

    /**
     * Operation "method" keys mapped to Operation values
     * @param array<string,Operation> $operations
     */
    private function reviewOperations(array $operations): void
    {
        if (empty($operations)) {
            $this->addWarning('No Operations on Path', Warning::EMPTY_PATH);
        }

        foreach ([Method::OPTIONS, Method::HEAD, Method::TRACE] as $method) {
            if (isset($operations[$method->value])) {
                $this->addWarning(
                    "$method->value is redundant in an OpenAPI Specification.",
                    Warning::REDUNDANT_METHOD,
                );
            }
        }
    }
}
