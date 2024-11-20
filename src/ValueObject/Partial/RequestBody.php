<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class RequestBody
{
    /**
     * @param MediaType[] $content
     */
    public function __construct(
        public string|null $description = null,
        public array $content = [],
        public bool $required = false,
    ) {
    }
}
