<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

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
     * "boolean", "object", "array", "number", "string", or "integer", "null"
     * @var Type[]
     */
    public readonly array $type;
    /** @var array<mixed>|null  */
    public readonly array|null $enum;
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



    public readonly float|int|null $exclusiveMaximum;
    public readonly float|int|null $exclusiveMinimum;
    public readonly float|int|null $maximum;
    public readonly float|int|null $minimum;

    /** @var Type[] */
    private readonly array $typesItCanBe;

    public function __construct(
        Identifier $identifier,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier);
        $this->type = $this->validateType($this->getIdentifier(), $schema->type);

        $this->allOf = $this->validateSubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateSubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateSubSchemas('oneOf', $schema->oneOf);

        $this->enum = $schema->enum;

        $this->maximum = $schema->maximum;
        $this->minimum = $schema->minimum;
        $this->exclusiveMaximum = is_bool($schema->exclusiveMaximum) ?
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31($this->getIdentifier(), 'exclusiveMaximum') :
            $schema->exclusiveMaximum;
        $this->exclusiveMinimum = is_bool($schema->exclusiveMinimum) ?
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31($this->getIdentifier(), 'exclusiveMinimum') :
            $schema->exclusiveMinimum;

        $this->typesItCanBe = array_map(fn($t) => Type::from($t), $this
            ->typesItCanBe());

        if (empty($this->typesItCanBe)) {
            $this->addWarning(
                'no data type can satisfy this schema',
                Warning::IMPOSSIBLE_SCHEMA
            );
        }
    }

    public function canBe(Type $type): bool
    {
        return in_array($type, $this->typesItCanBe);
    }

    public function canOnlyBe(Type $type): bool
    {
        return [$type] === $this->typesItCanBe;
    }

    public function canBePrimitive(): bool
    {
        foreach ($this->typesItCanBe as $typeItCanBe) {
            if ($typeItCanBe->isPrimitive()) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    private function typesItCanBe(): array
    {
        $possibilities = [Type::valuesForVersion(OpenAPIVersion::Version_3_0)];

        if (!empty($this->type)) {
            $possibilities[] = array_map(fn($t) => $t->value, $this->type);
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

    /**
     * @param null|string|array<string> $type
     * @return Type[]
     */
    private function validateType(Identifier $identifier, null|string|array $type): array
    {
        if (is_null($type)) {
            return [];
        }

        if (is_string($type)) {
            $type = [$type];
        }

        return array_map(
            fn($t) => Type::tryFromVersion(OpenAPIVersion::Version_3_1, $t) ??
                throw InvalidOpenAPI::invalidType($identifier, $t),
            $type
        );
    }

    /**
     * @param null|array<Partial\Schema> $subSchemas
     * @return null|array<Schema>
     */
    private function validateSubSchemas(string $keyword, ?array $subSchemas): ?array
    {
        if (!isset($subSchemas)) {
            return null;
        }

        if (empty($subSchemas)) {
            throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
        }

        $result = [];
        foreach ($subSchemas as $index => $subSchema) {
            $result[] = new Schema(
                $this->getIdentifier()->append("$keyword($index)"),
                $subSchema
            );
        }

        return $result;
    }

    private function validateNumericKeyword(
        string $keyword,
        bool|float|int|null $value
    ): float|int|null {
        if (is_bool($value)) {
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31($this->getIdentifier(), $keyword);
        }

        return $value;
    }
}
