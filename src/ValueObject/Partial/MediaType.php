<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class MediaType
{
    public function __construct(
        public ?string $contentType = null,
        public ?Schema $schema = null,
    ) {
    }
}
