<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject;

final class Limit
{
    public function __construct(
        public readonly float|int $limit,
        public readonly bool $exclusive,
    ) {
    }
}
