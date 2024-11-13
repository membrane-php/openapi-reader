<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

use Membrane\OpenAPIReader\ValueObject\Limit;

interface Schema
{
    public function getRelevantMinimum(): ?Limit;
    public function getRelevantMaximum(): ?Limit;
}
