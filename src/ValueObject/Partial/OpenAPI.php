<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class OpenAPI
{
    /**
     * @param ?string $openAPI Specification implementation
     * @param ?string $title of document
     * @param ?string $version of document
     * @param PathItem[] $paths
     */
    public function __construct(
        public ?string $openAPI = null,
        public ?string $title = null,
        public ?string $version = null,
        public array $paths = [],
    ) {
    }
}
