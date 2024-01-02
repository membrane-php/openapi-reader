<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class PathItem
{
    /**
     * @param ?string $path to PathItem
     * @param Parameter[] $parameters specified on PathItem
     * @param Operation[] $operations specified on PathItem
     */
    public function __construct(
        public ?string $path = null,
        public array $parameters = [],
        public array $operations = []
    ) {
    }
}
