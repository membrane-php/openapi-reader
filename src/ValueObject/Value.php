<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject;

final class Value
{
    public function __construct(
        public readonly mixed $value,
    ) {
    }
}
