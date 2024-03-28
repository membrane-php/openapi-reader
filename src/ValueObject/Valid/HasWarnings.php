<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

interface HasWarnings
{
    public function hasWarnings(): bool;

    public function getWarnings(): Warnings;
}
