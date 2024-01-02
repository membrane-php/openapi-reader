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
    /** @var Parameter[] */
    public readonly array $parameters;

    /**
     * Operation "method" keys mapped to Operation values
     * @var array<string,Operation>
     */
    private readonly array $operations;
    public readonly ?Operation $get;
    public readonly ?Operation $put;
    public readonly ?Operation $post;
    public readonly ?Operation $delete;
    public readonly ?Operation $options;
    public readonly ?Operation $head;
    public readonly ?Operation $patch;
    public readonly ?Operation $trace;

    public function __construct(
        Identifier $parentIdentifier,
        Partial\PathItem $pathItem,
    ) {
        parent::__construct($parentIdentifier);

        $this->parameters = $this->validateParameters($pathItem->parameters);

        $this->operations = $this->validateOperations($pathItem->operations);
        $this->get = $this->operations[Method::GET->value] ?? null;
        $this->put = $this->operations[Method::PUT->value] ?? null;
        $this->post = $this->operations[Method::POST->value] ?? null;
        $this->delete = $this->operations[Method::DELETE->value] ?? null;
        $this->options = $this->operations[Method::OPTIONS->value] ?? null;
        $this->head = $this->operations[Method::HEAD->value] ?? null;
        $this->patch = $this->operations[Method::PATCH->value] ?? null;
        $this->trace = $this->operations[Method::TRACE->value] ?? null;
    }

    /**
     * Operation "method" keys mapped to Operation values
     * @return array<string,Operation>
     */
    public function getOperations(): array
    {
        return $this->operations;
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
                if (
                    strcmp($parameter->name, $otherParameter->name) === 0 &&
                    $parameter->in === $otherParameter->in
                ) {
                    throw InvalidOpenAPI::duplicateParameters(
                        $this->getIdentifier(),
                        $parameter->name,
                        $parameter->in->value
                    );
                }

                if (strcasecmp($parameter->name, $otherParameter->name) === 0) {
                    $this->addWarning(
                        <<<TEXT
                        'This Path Item contains parameters with similar names: $parameter->name 
                         this may lead to confusion.',
                        TEXT,
                        Warning::SIMILAR_NAMES
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param Partial\Operation[] $partialOperations
     * @return array<string,Operation>
     */
    private function validateOperations(array $partialOperations): array
    {
        $result = [];

        if (empty($partialOperations)) {
            $this->addWarning('No Operations on Path', Warning::EMPTY_PATH);
        }

        foreach ($partialOperations as $partialOperation) {
            $method = Method::tryFrom($partialOperation->method);
            if ($method === null) {
                throw InvalidOpenAPI::unrecognisedMethod(
                    $this->getIdentifier(),
                    $partialOperation->method
                );
            }

            if ($method->isRedundant()) {
                $this->addWarning(
                    "$method->value is redundant in an OpenAPI Specification.",
                    Warning::REDUNDANT_METHOD,
                );
            }

            $result[$method->value] = new Operation(
                $this->getIdentifier(),
                $this->parameters,
                $partialOperation
            );
        }

        return $result;
    }
}
