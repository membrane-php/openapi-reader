<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Header
{

    /**
     * @param array<MediaType>|null $content
     */
    public function __construct(
        public string|null $description = null,
        public string|null $style = null,
        public bool|null $explode = null,
        public bool|null $required = null,
        public Schema|null $schema = null,
        public array|null $content = null,
    ) {
    }
}
