<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

interface HasIdentifier
{
    public function getIdentifier(): Identifier;
}
