<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Exception\SchemaShouldBeBoolean;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class Schema extends Validated implements Valid\Schema
{
    public readonly bool|Keywords $value;

    public function __construct(
        Identifier $identifier,
        bool|Partial\Schema $schema,
    ) {
        parent::__construct($identifier);

        if (is_bool($schema)) {
            $this->value = $schema;
        } else {
            try {
                $this->value = new Keywords($identifier, $schema);
            } catch (SchemaShouldBeBoolean $e) {
                $this->addWarning($e->getMessage(), Valid\Warning::IMPOSSIBLE_SCHEMA);
                $this->value = $e->getCode() === SchemaShouldBeBoolean::ALWAYS_TRUE;
            }
        }
    }

    public function canBe(Type $type): bool
    {
        return in_array($type->value, $this->typesItCanBe());
    }

    public function canOnlyBe(Type $type): bool
    {
        return [$type->value] === $this->typesItCanBe();
    }

    public function canBePrimitive(): bool
    {
        $types = array_map(fn($t) => Type::from($t), $this->typesItCanBe());

        foreach ($types as $typeItCanBe) {
            if ($typeItCanBe->isPrimitive()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function typesItCanBe(): array
    {
        if ($this->value === true) {
            return [
                Type::Null->value,
                ...Type::valuesForVersion(OpenAPIVersion::Version_3_0),
            ];
        } elseif ($this->value === false) {
            return [];
        } else {
            return $this->value->typesItCanBe();
        }
    }
}
