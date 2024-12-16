<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject;

use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;
use Stringable;

final class Value implements Stringable
{
    public function __construct(
        public readonly mixed $value,
    ) {
    }

    public function getType(): Type
    {
        if (is_array($this->value)) {
            return array_is_list($this->value) ? Type::Array : Type::Object;
        }

        if (is_bool($this->value)) {
            return Type::Boolean;
        }

        if (is_float($this->value)) {
            return Type::Number;
        }

        if (is_int($this->value)) {
            return Type::Integer;
        }

        if (is_string($this->value)) {
            return Type::String;
        }

        return Type::Null;
    }

    public function __toString(): string
    {
        return json_encode($this->value) ?:
            throw new RuntimeException('Failed to encode value');
    }
}
