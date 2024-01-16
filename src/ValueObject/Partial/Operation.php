<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Operation
{
    /**
     * @param Parameter[] $parameters
     */
    public function __construct(
        public ?string $operationId = null,
        public array $parameters = [],
    ) {
    }
}
