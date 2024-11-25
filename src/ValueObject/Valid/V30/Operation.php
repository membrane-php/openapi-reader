<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
//TODO valid operation needs to validateResponses
final class Operation extends Validated
{
    /**
     * @param array<int,Server> $servers
     * Optional, may be left empty.
     * If empty or unspecified, the array will contain the Path level servers
     *
     * @param Parameter[] $parameters
     * The list MUST NOT include duplicated parameters.
     * A unique parameter is defined by a combination of a name and location.
     *
     * @param string $operationId
     * Required by Membrane
     * MUST be unique, value is case-sensitive.
     *
     * @param Response[] $responses
     */
    private function __construct(
        Identifier $identifier,
        public readonly string $operationId,
        public readonly array $servers,
        public readonly array $parameters,
        public readonly RequestBody|null $requestBody,
        public readonly array $responses,
    ) {
        parent::__construct($identifier);

        $this->reviewServers($this->servers);
        $this->reviewParameters($this->parameters);
    }

    public function withoutServers(): Operation
    {
        return new Operation(
            $this->getIdentifier(),
            $this->operationId,
            [new Server($this->getIdentifier(), new Partial\Server('/'))],
            $this->parameters,
            $this->requestBody,
            $this->responses,
        );
    }

    /**
     * @param Server[] $pathServers
     * @param Parameter[] $pathParameters
     */
    public static function fromPartial(
        Identifier $parentIdentifier,
        array $pathServers,
        array $pathParameters,
        Method $method,
        Partial\Operation $operation,
    ): Operation {
        $operationId = $operation->operationId ??
            throw CannotSupport::missingOperationId(
                $parentIdentifier->fromEnd(0) ?? '',
                $method->value,
            );

        $identifier = $parentIdentifier->append("$operationId($method->value)");

        $servers = self::validateServers(
            $identifier,
            $pathServers,
            $operation->servers,
        );

        $parameters = self::validateParameters(
            $identifier,
            $pathParameters,
            $operation->parameters
        );

        $requestBody = isset($operation->requestBody) ?
            new RequestBody($identifier, $operation->requestBody) :
            null;

        $responses = self::validateResponses($identifier, $operation->responses);

        return new Operation(
            $identifier,
            $operationId,
            $servers,
            $parameters,
            $requestBody,
            $responses,
        );
    }

    /**
     * @param array<int,Server> $pathServers
     * @param Partial\Server[] $operationServers
     * @return array<int,Server>>
     */
    private static function validateServers(
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

        return $result;
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
     * @param Parameter[] $pathParameters
     * @param Partial\Parameter[] $operationParameters
     * @return Parameter[]
     */
    private static function validateParameters(
        Identifier $identifier,
        array $pathParameters,
        array $operationParameters
    ): array {
        $result = self::mergeParameters(
            $identifier,
            $operationParameters,
            $pathParameters
        );

        foreach ($result as $index => $parameter) {
            foreach (array_slice($result, $index + 1) as $otherParameter) {
                if ($parameter->isIdentical($otherParameter)) {
                    throw InvalidOpenAPI::duplicateParameters(
                        $identifier,
                        $parameter->getIdentifier(),
                        $otherParameter->getIdentifier(),
                    );
                }

                if ($parameter->canConflictWith($otherParameter)) {
                    throw CannotSupport::conflictingParameterStyles(
                        (string) $parameter->getIdentifier(),
                        (string) $otherParameter->getIdentifier(),
                    );
                }
            }
        }

        return $result;
    }

    /** @param array<int,Parameter> $parameters */
    private function reviewParameters(array $parameters): void
    {
        foreach ($parameters as $index => $parameter) {
            foreach (array_slice($parameters, $index + 1) as $otherParameter) {
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
            }
        }
    }

    /**
     * @param Partial\Parameter[] $operationParameters
     * @param Parameter[] $pathParameters
     * @return array<int,Parameter>
     */
    private static function mergeParameters(
        Identifier $identifier,
        array $operationParameters,
        array $pathParameters
    ): array {
        $result = array_map(
            fn($p) => new Parameter($identifier, $p),
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

    /**
     * @param Partial\Response[] $responses
     * @return array<string, Response>
     */
    private static function validateResponses(
        Identifier $identifier,
        array $responses,
    ): array
    {
        $result = [];

        foreach ($responses as $code => $response) {
            if (! is_numeric($code) && $code !== 'default') {
                throw InvalidOpenAPI::responseCodeMustBeNumericOrDefault(
                    $identifier,
                    (string) $code,
                );
            }

            $result[(string) $code] = new Response(
                $identifier->append('response', (string) $code),
                $response,
            );
        }

        return $result;
    }
}
