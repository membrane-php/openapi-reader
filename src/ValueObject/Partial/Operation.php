<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Operation
{
    /**
     * @param Server[] $servers
     * @param Parameter[] $parameters
     * @param Response[] $responses
     */
    public function __construct(
        public string|null $operationId = null,
        public array $servers = [],
        public array $parameters = [],
        public RequestBody|null $requestBody = null,
        public array $responses = [],
    ) {
    }
}
