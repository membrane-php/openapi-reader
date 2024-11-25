<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Response
{
    /**
     * @param array<string,Header> $headers,
     * @param MediaType[] $content
     */
    public function __construct(
        public string|null $description = null,
        public array $headers = [],
        public array $content = [],
    ) {
    }
}
