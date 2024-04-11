<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class Schema extends Validated
{
    /**
     * If specified, this keyword's value  MUST be one of the following:
     * "boolean", "object", "array", "number", "string", or "integer"
     */
    public readonly ?Type $type;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var ?array<int, self>
     */
    public readonly ?array $allOf;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var ?array<int, self>
     */
    public readonly ?array $anyOf;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var ?array<int, self>
     */
    public readonly ?array $oneOf;

    /**
     * @var Type[]
     */
    public readonly array $typesItCanBe;

    public function __construct(
        Identifier $identifier,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier);

        $this->type = $this->validateType($this->getIdentifier(), $schema->type);

        if (isset($schema->allOf)) {
            if (empty($schema->allOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->allOf = $this->getSubSchemas('allOf', $schema->allOf);
        } else {
            $this->allOf = null;
        }

        if (isset($schema->anyOf)) {
            if (empty($schema->anyOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->anyOf = $this->getSubSchemas('anyOf', $schema->anyOf);
        } else {
            $this->anyOf = null;
        }

        if (isset($schema->oneOf)) {
            if (empty($schema->oneOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->oneOf = $this->getSubSchemas('oneOf', $schema->oneOf);
        } else {
            $this->oneOf = null;
        }

        $this->typesItCanBe = array_map(
            fn($t) => Type::from($t),
            $this->typesItCanBe()
        );

        if (empty($this->typesItCanBe)) {
            $this->addWarning(
                'no data type can satisfy this schema',
                Warning::IMPOSSIBLE_SCHEMA
            );
        }
    }

    private function validateType(Identifier $identifier, ?string $type): ?Type
    {
        if (is_null($type)) {
            return null;
        }

        return Type::tryFromVersion(
            OpenAPIVersion::Version_3_0,
            $type
        ) ?? throw InvalidOpenAPI::invalidType($identifier, $type);
    }

    /**
     * @param Partial\Schema[] $subSchemas
     * @return self[]
     */
    private function getSubSchemas(string $keyword, array $subSchemas): array
    {
        $result = [];
        foreach ($subSchemas as $index => $subSchema) {
            $result[] = new Schema(
                $this->getIdentifier()->append("$keyword($index)"),
                $subSchema
            );
        }
        return $result;
    }

    public function canBe(Type $type): bool
    {
        return in_array($type, $this->typesItCanBe);
    }

    public function canOnlyBe(Type $type): bool
    {
        return [$type] === $this->typesItCanBe;
    }

    public function canOnlyBePrimitive(): bool
    {
        return !$this->canBe(Type::Array) && !$this->canBe(Type::Object);
    }

    /** @return string[] */
    private function typesItCanBe(): array
    {
        $possibilities = [Type::valuesForVersion(OpenAPIVersion::Version_3_0)];

        if ($this->type !== null) {
            $possibilities[] = [$this->type->value];
        }

        if (!empty($this->allOf)) {
            $possibilities[] = array_intersect(...array_map(
                fn($s) => $s->typesItCanBe(),
                $this->allOf,
            ));
        }

        if (!empty($this->anyOf)) {
            $possibilities[] = array_unique(array_merge(...array_map(
                fn($s) => $s->typesItCanBe(),
                $this->anyOf
            )));
        }

        if (!empty($this->oneOf)) {
            $possibilities[] = array_unique(array_merge(...array_map(
                fn($s) => $s->typesItCanBe(),
                $this->oneOf
            )));
        }

        return array_values(array_intersect(...$possibilities));
    }
}
