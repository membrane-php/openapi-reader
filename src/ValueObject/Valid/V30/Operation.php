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
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class Operation extends Validated
{
    /**
     * The list MUST NOT include duplicated parameters.
     * A unique parameter is defined by a combination of a name and location.
     * @var Parameter[]
     */
    public readonly array $parameters;

    /**
     * Required by Membrane
     * MUST be unique, value is case-sensitive.
     */
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

        $this->parameters = $this->validateParameters(
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
    private function validateParameters(
        array $pathParameters,
        array $operationParameters
    ): array {
        $result = $this->mergeParameters($operationParameters, $pathParameters);

        foreach ($result as $index => $parameter) {
            foreach (array_slice($result, $index + 1) as $otherParameter) {
                if ($this->areParametersSimilar($parameter, $otherParameter)) {
                    $this->addWarning(
                        <<<TEXT
                        'This contains confusingly similar parameter names:
                         $parameter->name
                         $otherParameter->name
                        TEXT,
                        Warning::SIMILAR_NAMES
                    );

                    if ($this->areParametersIdentical($parameter, $otherParameter)) {
                        throw InvalidOpenAPI::duplicateParameters(
                            $this->getIdentifier(),
                            $parameter->getIdentifier(),
                            $otherParameter->getIdentifier(),
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param Partial\Parameter[] $operationParameters
     * @param Parameter[] $pathParameters
     * @return array<int,Parameter>
     */
    private function mergeParameters(array $operationParameters, array $pathParameters): array
    {
        $result = array_map(
            fn($p) => new Parameter($this->getIdentifier(), $p),
            $operationParameters
        );

        foreach ($pathParameters as $pathParameter) {
            foreach ($result as $operationParameter) {
                if ($this->areParametersIdentical($pathParameter, $operationParameter)) {
                    break;
                }
                $result[] = $pathParameter;
            }
        }
        return array_values($result);
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
