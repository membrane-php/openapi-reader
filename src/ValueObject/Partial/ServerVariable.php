<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class ServerVariable
{
    /** @param string[] $enum */
    public function __construct(
        public ?string $name = null,
        public ?string $default = null,
        public ?array $enum = null,
    ) {
    }
}
