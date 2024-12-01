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
    /** @var list<Type> */
    public readonly array $types;

    /** @var list<Value>|null */
    public readonly array|null $enum;
    public readonly Value|null $default;

    public readonly float|int|null $multipleOf;
    public readonly Limit|null $maximum;
    public readonly Limit|null $minimum;

    public readonly int|null $maxLength;
    public readonly int $minLength;
    public readonly string|null $pattern;

    public readonly Schema $items;
    public readonly int|null $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public readonly int|null $maxProperties;
    public readonly int $minProperties;
    /** @var list<string>  */
    public readonly array $required;
    /** @var array<string, Schema> */
    public readonly array $properties;
    public readonly bool|Schema $additionalProperties;

    /** @var list<Schema> */
    public readonly array $allOf;
    /** @var list<Schema> */
    public readonly array $anyOf;
    /** @var list<Schema> */
    public readonly array $oneOf;
    public readonly bool|Schema $not;

    public readonly string $format;

    public readonly string $title;
    public readonly string $description;

    /** @var Type[] */
    private readonly array $typesItCanBe;

    //TODO how do we determine booleanSchemas
    // public readonly bool $isEmpty;

    //TODO can we limit additionalProperties and not to Schema?
    // We need to do so without creating an infinite loop of Schemas

    public function __construct(
        Identifier $identifier,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier);

        $this->types = $this->validateTypes(
            $identifier,
            $schema->type,
            $schema->nullable
        );

        //TODO reviewEnum is not empty, but allow it to stay empty
        $this->enum = $schema->enum;
        $this->default = $schema->default;

        $this->multipleOf = $this->validatePositiveNumber('multipleOf', $schema->multipleOf);
        $this->maximum = isset($schema->maximum) ?
            new Limit($schema->maximum, $this->validateExclusiveMinMax('exclusiveMaximum', $schema->exclusiveMaximum)) :
            null;
        $this->minimum = isset($schema->minimum) ?
            new Limit($schema->minimum, $this->validateExclusiveMinMax('exclusiveMinimum', $schema->exclusiveMinimum)) :
            null;

        $this->maxLength = $this->validateNonNegativeInteger('maxLength', $schema->maxLength, false);
        $this->minLength = $this->validateNonNegativeInteger('minLength', $schema->minLength, true);
        $this->pattern = $schema->pattern;

        $this->items = $this->validateItems($this->types, $schema->items);
        $this->maxItems = $this->validateNonNegativeInteger('maxItems', $schema->maxItems, false);
        $this->minItems = $this->validateNonNegativeInteger('minItems', $schema->minItems, true);
        $this->uniqueItems = $schema->uniqueItems;

        $this->maxProperties = $this->validateNonNegativeInteger('maxProperties', $schema->maxProperties, false);
        $this->minProperties = $this->validateNonNegativeInteger('minProperties', $schema->minProperties, true);
        $this->required = $this->validateRequired($schema->required);
        $this->properties = $this->validateProperties($schema->properties);
        //todo make it a boolean schema when required
        $this->additionalProperties = $schema->additionalProperties instanceof Partial\Schema ?
            new Schema($this->appendedIdentifier('additionalProperties'), $schema->additionalProperties) :
            $schema->additionalProperties;

        // make empty arrays instead of null
        $this->allOf = $this->validateSubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateSubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateSubSchemas('oneOf', $schema->oneOf);
        $this->not = $schema->not instanceof Partial\Schema ?
            new Schema($this->getIdentifier()->append('not'), $schema->not) :
            $schema->not;

        $this->format = $this->formatMetadataString($schema->format);

        $this->title = $this->formatMetadataString($schema->title);
        $this->description = $this->formatMetadataString($schema->description);

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

    /** @return string[] */
    private function typesItCanBe(): array
    {
        $possibilities = [Type::valuesForVersion(OpenAPIVersion::Version_3_0)];

        if ($this->types !== []) {
            $possibilities[] = $this->types;
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
     * @return list<Type>
     */
    private function validateTypes(
        Identifier $identifier,
        null|string|array $type,
        bool $nullable,
    ): array {
        if (empty($type)) { // If type is unspecified, nullable has no effect
            return []; // So we can return immediately
        }

        if (is_string($type)) {
            $type = [$type];
        } elseif (count($type) > 1) {
            throw InvalidOpenAPI::keywordMustBeType(
                $identifier,
                'type',
                Type::String,
            );
        }

        $result = array_map(
            fn($t) => Type::tryFromVersion(OpenAPIVersion::Version_3_0, $t)
                ?? throw InvalidOpenAPI::invalidType($identifier, $t),
            $type,
        );

        if ($nullable) {
            $result[] = Type::Null;
        }

        return $result;
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

    /** @return ($defaultsToZero is true ? int : int|null) */
    private function validateNonNegativeInteger(
        string $keyword,
        int|null $value,
        bool $defaultsToZero,
    ): int|null {
        if ($value !== null && $value < 0) {
            if (! $defaultsToZero) {
                throw InvalidOpenAPI::keywordMustBeNonNegativeInteger(
                    $this->getIdentifier(),
                    $keyword
                );
            } else {
                $this->addWarning("$keyword must not be negative", Warning::INVALID_API);
                return 0;
            }
        }

        return $value;
    }

    /**
     * @param array<string>|null $required
     * @return list<string>
     */
    private function validateRequired(array | null $required): array
    {
        if ($required === null) {
            return [];
        }

        if ($required === []) {
            $this->addWarning('required must not be empty', Warning::INVALID_API);
            return [];
        }

        $uniqueRequired = array_unique($required);

        if (count($required) !== count($uniqueRequired)) {
            $this->addWarning('required must not contain duplicates', Warning::INVALID_API);
        }

        return $uniqueRequired;
    }

    /**
     * @param null|array<Partial\Schema> $subSchemas
     * @return list<Schema>
     */
    private function validateSubSchemas(string $keyword, ?array $subSchemas): array
    {
        if ($subSchemas === null) {
            return [];
        }

        if ($subSchemas === []) {
            $this->addWarning("$keyword must not be empty", Warning::INVALID_API);
            return [];
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

    /** @param list<Type> $types */
    private function validateItems(array $types, Partial\Schema|null $items): Schema
    {
        if (in_array(Type::Array, $types) && !isset($items)) {
            $this->addWarning(
                'items must be specified, if type is specified as array',
                Warning::INVALID_API,
            );
        }

        return new Schema(
            $this->getIdentifier()->append('items'),
            $items ?? new Partial\Schema(),
        );
    }

    public function formatMetadataString(string $metadata): string
    {
        return trim($metadata);
    }
}
