<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Schema
{
    /**
     * @param ?self[] $allOf
     * @param ?self[] $anyOf
     * @param ?self[] $oneOf
     */
    public function __construct(
        public ?string $type = null,
        public ?array $allOf = null,
        public ?array $anyOf = null,
        public ?array $oneOf = null,
    ) {
    }
}
