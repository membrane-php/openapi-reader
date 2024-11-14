<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

interface Schema
{
    /** @return Type[] */
    public function getTypes(): array;
    public function getRelevantMinimum(): ?Limit;
    public function getRelevantMaximum(): ?Limit;
}
