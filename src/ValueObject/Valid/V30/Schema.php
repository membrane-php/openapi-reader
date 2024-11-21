<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

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
    public readonly Type|null $type;
    public readonly bool $nullable;
    /** @var array<Value>|null */
    public readonly array|null $enum;
    public readonly Value|null $default;

    public readonly float|int|null $multipleOf;
    public readonly bool $exclusiveMaximum;
    public readonly bool $exclusiveMinimum;
    public readonly float|int|null $maximum;
    public readonly float|int|null $minimum;

    public readonly int|null $maxLength;
    public readonly int $minLength;
    public readonly string|null $pattern;

    /** @var Schema|null  */
    public readonly Schema|null $items;
    public readonly int|null $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    /** @var array<string, Schema> */
    public readonly array $properties;
    public readonly int|null $maxProperties;
    public readonly int $minProperties;
    /** @var non-empty-array<string>|null  */
    public readonly array|null $required;
    public readonly bool|Schema $additionalProperties;

    /** @var non-empty-array<Schema>|null */
    public readonly array|null $allOf;
    /** @var non-empty-array<Schema>|null */
    public readonly ?array $anyOf;
    /** @var non-empty-array<Schema>|null */
    public readonly array|null $oneOf;
    public readonly Schema|null $not;

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
        $this->nullable = $schema->nullable;
        $this->enum = $schema->enum;
        $this->default = $schema->default;

        $this->multipleOf = $this->validatePositiveNumber('multipleOf', $schema->multipleOf);
        $this->maximum = $schema->maximum;
        $this->exclusiveMaximum = $this->validateExclusiveMinMax('exclusiveMaximum', $schema->exclusiveMaximum);
        $this->minimum = $schema->minimum;
        $this->exclusiveMinimum = $this->validateExclusiveMinMax('exclusiveMinimum', $schema->exclusiveMinimum);

        $this->maxLength = $this->validateNonNegativeInteger('maxLength', $schema->maxLength);
        $this->minLength = $this->validateNonNegativeInteger('minLength', $schema->minLength) ?? 0;
        $this->pattern = $schema->pattern;

        $this->items = $this->validateItems($this->type, $schema->items);
        $this->maxItems = $this->validateNonNegativeInteger('maxItems', $schema->maxItems);
        $this->minItems = $this->validateNonNegativeInteger('minItems', $schema->minItems) ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        $this->maxProperties = $this->validateNonNegativeInteger('maxProperties', $schema->maxProperties);
        $this->minProperties = $this->validateNonNegativeInteger('minProperties', $schema->minProperties) ?? 0;
        $this->required = $this->validateRequired($schema->required);
        $this->properties = $this->validateProperties($schema->properties);
        $this->additionalProperties = isset($schema->additionalProperties) ? (is_bool($schema->additionalProperties) ?
            $schema->additionalProperties :
            new Schema($this->getIdentifier()->append('additionalProperties'), $schema->additionalProperties)) :
            true;

        $this->allOf = $this->validateSubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateSubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateSubSchemas('oneOf', $schema->oneOf);
        $this->not = isset($schema->not) ?
            new Schema($this->getIdentifier()->append('not'), $schema->not) :
            null;

        $this->format = $schema->format;

        $this->title = $schema->title;
        $this->description = $schema->description;

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

    /** @return Type[] */
    public function getTypes(): array
    {
        $result = isset($this->type) ?
            [$this->type] :
            Type::casesForVersion(OpenAPIVersion::Version_3_0);

        if ($this->nullable) {
            $result[] = Type::Null;
        }

        return $result;
    }

    public function getRelevantMaximum(): ?Limit
    {
        return isset($this->maximum) ?
            new Limit($this->maximum, $this->exclusiveMaximum) :
            null;
    }

    public function getRelevantMinimum(): ?Limit
    {
        return isset($this->minimum) ?
            new Limit($this->minimum, $this->exclusiveMinimum) :
            null;
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

    /** @param null|string|array<string> $type */
    private function validateType(Identifier $identifier, null|string|array $type): ?Type
    {
        if (is_null($type)) {
            return null;
        }

        if (is_array($type)) {
            throw InvalidOpenAPI::typeArrayInWrongVersion($identifier);
        }

        return Type::tryFromVersion(
            OpenAPIVersion::Version_3_0,
            $type
        ) ?? throw InvalidOpenAPI::invalidType($identifier, $type);
    }

    private function validateExclusiveMinMax(
        string $keyword,
        bool|float|int|null $exclusiveMinMax,
    ): bool {
        if (is_float($exclusiveMinMax) || is_integer($exclusiveMinMax)) {
            throw InvalidOpenAPI::numericExclusiveMinMaxIn30($this->getIdentifier(), $keyword);
        }

        return $exclusiveMinMax ?? false;
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
     * @return non-empty-array<string>|null
     */
    private function validateRequired(array|null $value): array|null
    {
        if ($value === null) {
            return $value;
        }

        if ($value === []) {
            throw InvalidOpenAPI::mustBeNonEmpty($this->getIdentifier(), 'required');
        }

        if (count($value) !== count(array_unique($value))) {
            throw InvalidOpenAPI::mustContainUniqueItems($this->getIdentifier(), 'required');
        }

        return $value;
    }

    /**
     * @param null|array<Partial\Schema> $subSchemas
     * @return null|non-empty-array<Schema>
     */
    private function validateSubSchemas(string $keyword, ?array $subSchemas): ?array
    {
        if ($subSchemas === null) {
            return null;
        }

        if ($subSchemas === []) {
            throw InvalidOpenAPI::mustBeNonEmpty($this->getIdentifier(), $keyword);
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

    /**
     * @param array<string, Partial\Schema> $properties
     * @return array<string, Schema>
     */
    private function validateProperties(?array $properties): array
    {
        $properties ??= [];

        $result = [];
        foreach ($properties as $key => $subSchema) {
            if (!is_string($key)) {
                throw InvalidOpenAPI::mustHaveStringKeys(
                    $this->getIdentifier(),
                    'properties',
                );
            }

            $result[$key] = new Schema(
                $this->getIdentifier()->append("properties($key)"),
                $subSchema
            );
        }

        return $result;
    }

    private function validateItems(Type|null $type, Partial\Schema|null $items): Schema|null
    {
        if (is_null($items)) {
            //@todo update tests to support this validation
            //if ($type == Type::Array) {
            //    throw InvalidOpenAPI::mustSpecifyItemsForArrayType($this->getIdentifier());
            //}

            return $items;
        }

        return new Schema($this->getIdentifier()->append('items'), $items);
    }
}
