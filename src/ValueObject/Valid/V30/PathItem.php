<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class PathItem extends Validated
{
    /**
     * Optional, may be left empty.
     * If empty or unspecified, the array will contain the OpenAPI level servers
     * @var array<int,Server>
     */
    public readonly array $servers;

    /**
     * The list MUST NOT include duplicated parameters.
     * A unique parameter is defined by a combination of a name and location.
     * @var array<int,Parameter>
     */
    public readonly array $parameters;

    public readonly ?Operation $get;
    public readonly ?Operation $put;
    public readonly ?Operation $post;
    public readonly ?Operation $delete;
    public readonly ?Operation $options;
    public readonly ?Operation $head;
    public readonly ?Operation $patch;
    public readonly ?Operation $trace;

    /**
     * @param array<int,Server> $openAPIServers
     */
    public function __construct(
        Identifier $identifier,
        array $openAPIServers,
        Partial\PathItem $pathItem,
    ) {
        parent::__construct($identifier);

        $this->servers = $this->validateServers(
            $identifier,
            $openAPIServers,
            $pathItem->servers,
        );

        $this->parameters = $this->validateParameters($pathItem->parameters);

        $this->get = $this->validateOperation(Method::GET, $pathItem->get);
        $this->put = $this->validateOperation(Method::PUT, $pathItem->put);
        $this->post = $this->validateOperation(Method::POST, $pathItem->post);
        $this->delete = $this->validateOperation(Method::DELETE, $pathItem->delete);
        $this->options = $this->validateOperation(Method::OPTIONS, $pathItem->options);
        $this->head = $this->validateOperation(Method::HEAD, $pathItem->head);
        $this->patch = $this->validateOperation(Method::PATCH, $pathItem->patch);
        $this->trace = $this->validateOperation(Method::TRACE, $pathItem->trace);

        $this->checkOperations($this->getOperations());
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
    private function validateServers(
        Identifier $identifier,
        array $openAPIServers,
        array $pathServers
    ): array {
        if (empty($pathServers)) {
            return $openAPIServers;
        }

        return array_values(
            array_map(fn($s) => new Server($identifier, $s), $pathServers)
        );
    }

    /**
     * @param Partial\Parameter[] $parameters
     * @return Parameter[]
     */
    private function validateParameters(array $parameters): array
    {
        $result = array_map(
            fn($p) => new Parameter($this->getIdentifier(), $p),
            $parameters
        );

        foreach (array_values($result) as $index => $parameter) {
            foreach (array_slice($result, $index + 1) as $otherParameter) {
                if ($this->areParametersIdentical($parameter, $otherParameter)) {
                    throw InvalidOpenAPI::duplicateParameters(
                        $this->getIdentifier(),
                        $parameter->getIdentifier(),
                        $otherParameter->getIdentifier(),
                    );
                }

                if ($this->areParametersSimilar($parameter, $otherParameter)) {
                    $this->addWarning(
                        <<<TEXT
                        'This contains confusingly similar parameter names:
                         $parameter->name
                         $otherParameter->name
                        TEXT,
                        Warning::SIMILAR_NAMES
                    );
                }
            }
        }

        return $result;
    }

    private function areParametersIdentical(
        Parameter $parameter,
        Parameter $otherParameter
    ): bool {
        return $parameter->name === $otherParameter->name &&
            $parameter->in === $otherParameter->in;
    }

    private function areParametersSimilar(
        Parameter $parameter,
        Parameter $otherParameter
    ): bool {
        return strcasecmp($parameter->name, $otherParameter->name) === 0;
    }

    private function validateOperation(
        Method $method,
        ?Partial\Operation $operation
    ): ?Operation {
        if (is_null($operation)) {
            return null;
        }

        return new Operation(
            $this->getIdentifier(),
            $this->servers,
            $this->parameters,
            $method,
            $operation
        );
    }

    /**
     * Operation "method" keys mapped to Operation values
     * @param array<string,Operation> $operations
     */
    private function checkOperations(array $operations): void
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
