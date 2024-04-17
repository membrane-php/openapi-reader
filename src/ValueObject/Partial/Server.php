<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Server
{
    /**
     * @param ServerVariable[] $variables
     */
    public function __construct(
        public ?string $url = null,
        public array $variables = [],
    ) {
    }
}
