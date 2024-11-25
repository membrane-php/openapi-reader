<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;

interface Parameter
{
    public function getSchema(): Schema;

    /**
     * @phpstan-assert-if-true string $this->getMediaType()
     * @phpstan-assert-if-false null $this->getMediaType()
     */
    public function hasMediaType(): bool;
    public function getMediaType(): ?string;
}
