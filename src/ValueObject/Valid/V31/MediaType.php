<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class MediaType extends Validated
{
    public readonly ?Schema $schema;

    public function __construct(
        Identifier $identifier,
        Partial\MediaType $mediaType
    ) {
         parent::__construct($identifier);

        $this->schema = isset($mediaType->schema) ?
            new Schema($this->getIdentifier()->append('schema'), $mediaType->schema) :
            null;
    }
}
