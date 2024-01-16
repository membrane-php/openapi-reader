<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

final class PathItem
{
    /**
     * @param ?string $path to PathItem
     * @param Parameter[] $parameters specified on PathItem
     */
    public function __construct(
        public ?string $path = null,
        public array $parameters = [],
        public ?Operation $get = null,
        public ?Operation $put = null,
        public ?Operation $post = null,
        public ?Operation $delete = null,
        public ?Operation $options = null,
        public ?Operation $head = null,
        public ?Operation $patch = null,
        public ?Operation $trace = null,
    ) {
    }
}
