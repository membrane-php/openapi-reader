<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Schema
{
    /**
     * @param array<string>|string|null $type
     * @param ?self[] $allOf
     * @param ?self[] $anyOf
     * @param ?self[] $oneOf
     */
    public function __construct(
        public array|string|null $type = null,
        public ?array $allOf = null,
        public ?array $anyOf = null,
        public ?array $oneOf = null,
    ) {
    }
}
