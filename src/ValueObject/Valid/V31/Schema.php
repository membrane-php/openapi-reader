<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Value;

final class Schema extends Validated implements Valid\Schema
{
    /** @var Type[]|null */
    public readonly array|null $type;
    /** @var array<Value>|null  */
    public readonly array|null $enum;
    public readonly Value|null $default;

    public readonly float|int|null $multipleOf;
    public readonly float|int|null $exclusiveMaximum;
    public readonly float|int|null $exclusiveMinimum;
    public readonly float|int|null $maximum;
    public readonly float|int|null $minimum;

    public readonly int|null $maxLength;
    public readonly int $minLength;
    public readonly string|null $pattern;

    public readonly int|null $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public readonly int|null $maxProperties;
    public readonly int $minProperties;
    /** @var array<string>  */
    public readonly array $required;

    /** @var non-empty-array<Schema>|null */
    public readonly array|null $allOf;
    /** @var non-empty-array<Schema>|null */
    public readonly ?array $anyOf;
    /** @var non-empty-array<Schema>|null */
    public readonly array|null $oneOf;
    public readonly Schema|null $not;

    public readonly Schema|null $items;

    /** @var array<string,Schema> */
    public readonly array $properties;
    public readonly Schema|bool $additionalProperties;

    public readonly string|null $format;

    public readonly string|null $title;
    public readonly string|null $description;

    /** @var Type[] */
    private readonly array $typesItCanBe;

    public function __construct(
        Identifier $identifier,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier);
        $this->type = $this->validateType($this->getIdentifier(), $schema->type);
        $this->enum = $schema->enum;
        $this->default = $schema->default;

        $this->multipleOf = $this->validatePositiveNumber('multipleOf', $schema->multipleOf);
        $this->exclusiveMaximum = $this->validateExclusiveMinMax('exclusiveMaximum', $schema->exclusiveMaximum);
        $this->exclusiveMinimum = $this->validateExclusiveMinMax('exclusiveMinimum', $schema->exclusiveMinimum);
        $this->maximum = $schema->maximum;
        $this->minimum = $schema->minimum;

        $this->maxLength = $this->validateNonNegativeInteger('maxLength', $schema->maxLength);
        $this->minLength = $this->validateNonNegativeInteger('minLength', $schema->minLength) ?? 0;
        $this->pattern = $schema->pattern;

        $this->maxItems = $this->validateNonNegativeInteger('maxItems', $schema->maxItems);
        $this->minItems = $this->validateNonNegativeInteger('minItems', $schema->minItems) ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        $this->maxProperties = $this->validateNonNegativeInteger('maxProperties', $schema->maxProperties);
        $this->minProperties = $this->validateNonNegativeInteger('minProperties', $schema->minProperties) ?? 0;
        $this->required = $this->validateRequired($schema->required);

        $this->allOf = $this->validateNonEmptySubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateNonEmptySubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateNonEmptySubSchemas('oneOf', $schema->oneOf);
        $this->not = isset($schema->not) ?
            new Schema($this->getIdentifier()->append('not'), $schema->not) :
            null;

        $this->items = $this->validateItems($schema->items);

        $this->properties = $this->validateStringKeySchemaArray('properties', $schema->properties);
        $this->additionalProperties = is_bool($schema->additionalProperties) ?
            $schema->additionalProperties :
            new Schema($this->getIdentifier()->append('additionalProperties'), $schema->additionalProperties);

        $this->format = $schema->format;

        $this->title = $schema->title;
        $this->description = $schema->description;

        $this->typesItCanBe = array_map(fn($t) => Type::from($t), $this
            ->typesItCanBe());

        if (empty($this->typesItCanBe)) {
            $this->addWarning(
                'no data type can satisfy this schema',
                Warning::IMPOSSIBLE_SCHEMA
            );
        }
    }

    /** @return Type[] */
    public function getTypes(): array
    {
        return $this->type ?? Type::casesForVersion(OpenAPIVersion::Version_3_1);
    }

    public function getRelevantMaximum(): ?Limit
    {
        if (isset($this->maximum)) {
            if (isset($this->exclusiveMaximum) && $this->exclusiveMaximum <= $this->maximum) {
                return new Limit($this->exclusiveMaximum, true);
            }
            return new Limit($this->maximum, false);
        }

        if (isset($this->exclusiveMaximum)) {
            return new Limit($this->exclusiveMaximum, true);
        }

        return null;
    }

    public function getRelevantMinimum(): ?Limit
    {
        if (isset($this->minimum)) {
            if (isset($this->exclusiveMinimum) && $this->exclusiveMinimum >= $this->minimum) {
                return new Limit($this->exclusiveMinimum, true);
            }
            return new Limit($this->minimum, false);
        }

        if (isset($this->exclusiveMinimum)) {
            return new Limit($this->exclusiveMinimum, true);
        }

        return null;
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
     * @return Type[]|null
     */
    private function validateType(Identifier $identifier, null|string|array $type): array|null
    {
        if (is_null($type)) {
            return null;
        }

        if (is_string($type)) {
            $type = [$type];
        }

        if (count($type) !== count(array_unique($type))) {
            throw InvalidOpenAPI::mustContainUniqueItems($identifier, 'type');
        }

        return array_map(
            fn($t) => Type::tryFromVersion(OpenAPIVersion::Version_3_1, $t) ??
                throw InvalidOpenAPI::invalidType($identifier, $t),
            $type
        );
    }

    private function validateExclusiveMinMax(
        string $keyword,
        bool|float|int|null $exclusiveMinMax,
    ): float|int|null {
        if (is_bool($exclusiveMinMax)) {
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31($this->getIdentifier(), $keyword);
        }

        return $exclusiveMinMax;
    }

    private function validatePositiveNumber(
        string $keyword,
        float|int|null $value
    ): float|int|null {
        if ($value !== null && $value <= 0) {
            throw InvalidOpenAPI::keywordMustBeStrictlyPositiveNumber($this->getIdentifier(), $keyword);
        }

        return $value;
    }

    private function validateNonNegativeInteger(
        string $keyword,
        int|null $value
    ): int|null {
        if ($value !== null && $value < 0) {
            throw InvalidOpenAPI::keywordMustBeNegativeInteger($this->getIdentifier(), $keyword);
        }

        return $value;
    }

    /**
     * @param array<string>|null $value
     * @return array<string>
     */
    private function validateRequired(array|null $value): array
    {
        $value ??= [];

        if (count($value) !== count(array_unique($value))) {
            throw InvalidOpenAPI::mustContainUniqueItems($this->getIdentifier(), 'required');
        }

        return $value;
    }

    /**
     * @param null|array<Partial\Schema> $subSchemas
     * @return null|non-empty-array<Schema>
     */
    private function validateNonEmptySubSchemas(string $keyword, ?array $subSchemas): ?array
    {
        if (!isset($subSchemas)) {
            return null;
        }

        if (empty($subSchemas)) {
            throw InvalidOpenAPI::mustBeNonEmpty($this->getIdentifier(), $keyword);
        }

        $result = [];
        foreach ($subSchemas as $index => $subSchema) {
            $result[$index] = new Schema(
                $this->getIdentifier()->append("$keyword($index)"),
                $subSchema
            );
        }

        return $result;
    }

    /**
     * @param null|array<Partial\Schema> $subSchemas
     * @return array<Schema>
     */
    private function validateStringKeySchemaArray(string $keyword, ?array $subSchemas): array
    {
        $subSchemas ??= [];

        $result = [];
        foreach ($subSchemas as $key => $subSchema) {
            if (!is_string($key)) {
                throw InvalidOpenAPI::mustHaveStringKeys(
                    $this->getIdentifier(),
                    $keyword,
                );
            }

            $result[$key] = new Schema(
                $this->getIdentifier()->append("$keyword($key)"),
                $subSchema
            );
        }

        return $result;
    }

    /**
     * @param array<Partial\Schema>|Partial\Schema|Partial\Schema|null $items
     */
    private function validateItems(array|Partial\Schema|null $items): Schema|null
    {
        if (is_array($items)) {
            throw InvalidOpenAPI::arrayItemsIn31($this->getIdentifier());
        }

        if (is_null($items)) {
            return $items;
        }

        return new Schema($this->getIdentifier()->append('items'), $items);
    }
}
