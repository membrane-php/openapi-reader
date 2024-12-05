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

final class Keywords extends Validated implements Valid\Schema
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
    public readonly Schema $not;

    public readonly string $format;

    public readonly string $title;
    public readonly string $description;

    /** @var Type[] */
    private readonly array $typesItCanBe;

    public function __construct(
        Identifier $identifier,
        Valid\Warnings $warnings,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier, $warnings);

        $this->types = $this->validateTypes($schema->type, $schema->nullable);
        $this->enum = $this->reviewEnum($this->types, $schema->enum);
        $this->default = $this->validateDefault($this->types, $schema->default);

        $this->multipleOf = $this->validateMultipleOf($schema->multipleOf);
        $this->maximum = $this->validateMinMax(
            'maximum',
            $schema->maximum,
            'exclusiveMaximum',
            $schema->exclusiveMaximum,
        );
        $this->minimum = $this->validateMinMax(
            'minimum',
            $schema->minimum,
            'exclusiveMinimum',
            $schema->exclusiveMinimum
        );

        $this->maxLength = $this->validateNonNegativeInteger('maxLength', $schema->maxLength, false);
        $this->minLength = $this->validateNonNegativeInteger('minLength', $schema->minLength, true);
        $this->pattern = $schema->pattern; //TODO validatePattern is valid regex

        $this->items = $this->reviewItems($this->types, $schema->items);
        $this->maxItems = $this->validateNonNegativeInteger('maxItems', $schema->maxItems, false);
        $this->minItems = $this->validateNonNegativeInteger('minItems', $schema->minItems, true);
        $this->uniqueItems = $schema->uniqueItems;

        $this->maxProperties = $this->validateNonNegativeInteger('maxProperties', $schema->maxProperties, false);
        $this->minProperties = $this->validateNonNegativeInteger('minProperties', $schema->minProperties, true);
        //TODO if a property is defined as a false schema AND required, that should be a warning
        //TODO if property is false schema and required, and type must be object, the whole schema is the false schema
        $this->required = $this->validateRequired($schema->required);
        $this->properties = $this->validateProperties($schema->properties);
        $this->additionalProperties = new Schema(
            $this->appendedIdentifier('additionalProperties'),
            $schema->additionalProperties,
        );


        //TODO throw ShouldBeBooleanSchema::false if allOf contains false schema
        $this->allOf = $this->validateSubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateSubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateSubSchemas('oneOf', $schema->oneOf);
        $this->not = new Schema(
            $this->appendedIdentifier('not'),
            $schema->not ?? false,
        );

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

    /** @return string[] */
    public function typesItCanBe(): array
    {
        $possibilities = [array_merge(
            Type::valuesForVersion(OpenAPIVersion::Version_3_0),
            [Type::Null->value],
        )];

        if ($this->types !== []) {
            $possibilities[] = array_map(fn($t) => $t->value, $this->types);
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
        null|string|array $type,
        bool $nullable,
    ): array {
        if (isset($this->types)) {
            return $this->types;
        }

        if (empty($type)) { // If type is unspecified, nullable has no effect
            return []; // So we can return immediately
        }

        if (is_string($type)) { // In 3.0 "type" must be a string, a single item
            $type = [$type];
        } elseif (count($type) > 1) { // We can amend arrays of 1 but no greater
            throw InvalidOpenAPI::keywordMustBeType(
                $this->getIdentifier(),
                'type',
                Type::String,
            );
        }

        $result = array_map(
            fn($t) => Type::tryFromVersion(OpenAPIVersion::Version_3_0, $t)
                ?? throw InvalidOpenAPI::invalidType($this->getIdentifier(), $t),
            $type,
        );

        if ($nullable) {
            $result[] = Type::Null;
        }

        return $result;
    }

    /**
     * @param list<Type> $types
     * @param list<Value>|null $enum
     * @return list<Value>
     */
    private function reviewEnum(
        array $types,
        array|null $enum,
    ): array {
        if ($enum === null) {
            return [];
        }

        if ($enum === []) {
            $this->addWarning('enum should not be empty', Warning::EMPTY_ENUM);
            return $enum;
        }

        if ($types === []) {
            return $enum;
        }

        $enumContainsValidValue = false;
        foreach ($enum as $value) {
            if (in_array($value->getType(), $types)) {
                $enumContainsValidValue = true;
            } else {
                $this->addWarning(
                    "$value does not match allowed types",
                    Warning::MISLEADING,
                );
            }
        }

        if (! $enumContainsValidValue) {
            throw Valid\Exception\SchemaShouldBeBoolean::alwaysFalse(
                $this->getIdentifier(),
                'enum does not contain any valid values',
            );
        }

        return array_values($enum);
    }

    /**
     * @param list<Type> $types
     */
    private function validateDefault(array $types, Value|null $default): Value|null
    {
        if ($default === null) {
            return null;
        }

        if (! in_array($default->getType(), $types)) {
            throw InvalidOpenAPI::defaultMustConformToType($this->getIdentifier());
        }

        return $default;
    }

    private function validateMultipleOf(float|int|null $value): float|int|null
    {
        if ($value === null || $value > 0) {
            return $value;
        }

        if ($value < 0) {
            $this->addWarning(
                'multipleOf must be greater than zero',
                Warning::INVALID,
            );
            return abs($value);
        }

        throw InvalidOpenAPI::keywordCannotBeZero(
            $this->getIdentifier(),
            'multipleOf'
        );
    }

    private function validateMinMax(
        string $keyword,
        float|int|null $minMax,
        string $exclusiveKeyword,
        bool|float|int|null $exclusiveMinMax,
    ): Limit|null {
        if (is_float($exclusiveMinMax) || is_integer($exclusiveMinMax)) {
            throw InvalidOpenAPI::numericExclusiveMinMaxIn30(
                $this->getIdentifier(),
                $exclusiveKeyword,
            );
        }

        if ($minMax === null) {
            if ($exclusiveMinMax === true) {
                $this->addWarning(
                    "$exclusiveKeyword has no effect without $keyword",
                    Warning::REDUNDANT,
                );
            }
            return null;
        }

        return new Limit($minMax, $exclusiveMinMax ?? false);
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
                $this->addWarning("$keyword must not be negative", Warning::INVALID);
                return 0;
            }
        }

        return $value;
    }

    /** @param list<Type> $types */
    private function reviewItems(
        array $types,
        Partial\Schema|null $items
    ): Schema {
        if (in_array(Type::Array, $types) && ! isset($items)) {
            $this->addWarning(
                'items must be specified, if type is array',
                Warning::INVALID,
            );
        }

        return new Schema(
            $this->getIdentifier()->append('items'),
            $items ?? true,
        );
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
            $this->addWarning('required must not be empty', Warning::INVALID);
            return [];
        }

        $uniqueRequired = array_unique($required);

        if (count($required) !== count($uniqueRequired)) {
            $this->addWarning('required must not contain duplicates', Warning::INVALID);
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
            $this->addWarning("$keyword must not be empty", Warning::INVALID);
            return [];
        }

        $result = [];
        foreach ($subSchemas as $index => $subSchema) {
            $identifier = $this->appendedIdentifier($keyword, sprintf(
                empty(trim($subSchema->title ?? '')) ? '%s' : '%2$s[%1$s]',
                $index,
                trim($subSchema->title ?? ''),
            ));
            $result[] = new Schema($identifier, $subSchema);
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

    public function formatMetadataString(string|null $metadata): string
    {
        return trim($metadata ?? '');
    }
}
