<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class ServerVariable
{
    public function __construct(
        public ?string $name = null,
        public ?string $default = null,
        public ?array $enum = null,
    ) {
    }
}
