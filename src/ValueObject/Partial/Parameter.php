<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class Parameter
{
    /**
     * @param MediaType[] $content
     */
    public function __construct(
        public ?string $name = null,
        public ?string $in = null,
        public ?bool $required = null,
        public ?string $style = null,
        public ?bool $explode = null,
        public ?Schema $schema = null,
        public array $content = [],
    ) {
    }
}
