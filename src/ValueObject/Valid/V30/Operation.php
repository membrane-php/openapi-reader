<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class Operation extends Validated
{
    /**
     * Optional, may be left empty.
     * If empty or unspecified, the array will contain the Path level servers
     * @var array<int,Server>
     */
    public readonly array $servers;

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
     * @param Server[] $pathServers
     * @param Parameter[] $pathParameters
     */
    public function __construct(
        Identifier $parentIdentifier,
        array $pathServers,
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

        $this->servers = $this->validateServers(
            $this->getIdentifier(),
            $pathServers,
            $operation->servers,
        );

        $this->parameters = $this->validateParameters(
            $pathParameters,
            $operation->parameters
        );
    }

    /**
     * @param array<int,Server> $pathServers
     * @param Partial\Server[] $operationServers
     * @return array<int,Server>>
     */
    private function validateServers(
        Identifier $identifier,
        array $pathServers,
        array $operationServers
    ): array {
        if (empty($operationServers)) {
            return $pathServers;
        }

        $result = array_values(array_map(
            fn($s) => new Server($identifier, $s),
            $operationServers
        ));

        $uniqueURLS = array_unique(array_map(fn($s) => $s->url, $result));
        if (count($result) !== count($uniqueURLS)) {
            $this->addWarning(
                'Server URLs are not unique',
                Warning::IDENTICAL_SERVER_URLS
            );
        }

        return $result;
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
                if ($parameter->isIdentical($otherParameter)) {
                    throw InvalidOpenAPI::duplicateParameters(
                        $this->getIdentifier(),
                        $parameter->getIdentifier(),
                        $otherParameter->getIdentifier(),
                    );
                }

                if ($parameter->isSimilar($otherParameter)) {
                    $this->addWarning(
                        <<<TEXT
                        'This contains confusingly similar parameter names:
                         $parameter->name
                         $otherParameter->name
                        TEXT,
                        Warning::SIMILAR_NAMES
                    );
                }

                if ($parameter->canConflict($otherParameter)) {
                    throw CannotSupport::conflictingParameterStyles(
                        (string) $parameter->getIdentifier(),
                        (string) $otherParameter->getIdentifier(),
                    );
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
                if ($operationParameter->isIdentical($pathParameter)) {
                    continue 2;
                }
            }
            $result[] = $pathParameter;
        }

        return array_values($result);
    }
}
