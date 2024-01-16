<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\In;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\Style;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class Operation extends Validated
{
    /** @var Parameter[] */
    public readonly array $parameters;

    public readonly string $operationId;

    /**
     * @param Parameter[] $pathParameters
     */
    public function __construct(
        Identifier $parentIdentifier,
        array $pathParameters,
        Method $method,
        Partial\Operation $operation,
    ) {
        $this->operationId = $operation->operationId ??
            throw CannotSupport::missingOperationId(
                $parentIdentifier->fromEnd(0) ?? '',
                $method->value,
            );

        parent::__construct($parentIdentifier->append("$this->operationId($method->value)"));

        $this->parameters = $this->mergeParameters(
            $pathParameters,
            $operation->parameters
        );

        $parametersThatCanConflict = array_filter($this->parameters, fn($p) => $this->canParameterConflict($p));
        if (count($parametersThatCanConflict) > 1) {
            throw CannotSupport::conflictingParameterStyles(
                ...array_map(fn($p) => (string)$p->getIdentifier(), $parametersThatCanConflict)
            );
        }
    }

    /**
     * @param Parameter[] $pathParameters
     * @param Partial\Parameter[] $operationParameters
     * @return Parameter[]
     */
    private function mergeParameters(
        array $pathParameters,
        array $operationParameters
    ): array {
        $result = array_map(
            fn($p) => new Parameter($this->getIdentifier(), $p),
            $operationParameters
        );

        foreach ($pathParameters as $pathParameter) {
            if (!$this->isIdenticalParameterInList($pathParameter, $result)) {
                $result[] = $pathParameter;
            }
        }

        foreach (array_values($result) as $index => $parameter) {
            if ($this->isIdenticalParameterInList($parameter, array_slice(array_values($result), $index + 1))) {
                throw InvalidOpenAPI::duplicateParameters(
                    $this->getIdentifier(),
                    $parameter->name,
                    $parameter->in->value
                );
            }
        }

        return $result;
    }

    /** @param Parameter[] $otherParameters */
    private function isIdenticalParameterInList(
        Parameter $parameter,
        array $otherParameters
    ): bool {
        foreach ($otherParameters as $otherParameter) {
            if (!$this->isParameterUnique($parameter, $otherParameter)) {
                return true;
            }
        }
        return false;
    }

    private function isParameterUnique(
        Parameter $parameter,
        Parameter $otherParameter
    ): bool {
        return $parameter->name !== $otherParameter->name ||
            $parameter->in !== $otherParameter->in;
    }



    private function canParameterConflict(Parameter $parameter): bool
    {
        if ($parameter->in !== In::Query) {
            return false;
        }

        $canBeObject = $parameter->getSchema()->canItBeAnObject();
        $canBeArray = $parameter->getSchema()->canItBeAnArray();

        return match ($parameter->style) {
            Style::Form => $canBeObject && $parameter->explode,
            Style::PipeDelimited, Style::SpaceDelimited => $canBeObject || $canBeArray,
            default => false,
        };
    }
}
