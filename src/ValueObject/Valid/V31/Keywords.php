<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Exception\SchemaShouldBeBoolean;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Value;

final class Keywords extends Validated implements Valid\Schema
{
    /** @var list<Type> */
    public readonly array $types;

    /** @var non-empty-list<Value>|null */
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
    public readonly Schema $additionalProperties;

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

        $this->types = $this->validateTypes($schema->type);
        $this->enum = $this->reviewEnum($this->types, $schema->const, $schema->enum);
        $this->default = $this->validateDefault($this->types, $schema->default);

        $this->multipleOf = $this->validateMultipleOf($schema->multipleOf);
        $this->maximum = $this->validateMaximum($schema->maximum, $schema->exclusiveMaximum);
        $this->minimum = $this->validateMinimum($schema->minimum, $schema->exclusiveMinimum);

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
    private function validateTypes(null|string|array $type): array
    {
        if (empty($type)) {
            return [];
        }

        if (is_string($type)) {
            $type = [$type];
        }

        return array_map(
            fn($t) => Type::tryFromVersion(OpenAPIVersion::Version_3_1, $t)
                ?? throw InvalidOpenAPI::invalidType($this->getIdentifier(), $t),
            $type,
        );
    }

    /**
     * @param list<Type> $types
     * @param list<Value>|null $enum
     * @return non-empty-list<Value>|null
     */
    private function reviewEnum(
        array $types,
        Value|null $const,
        array|null $enum,
    ): array|null {
        if ($const !== null) {
            if (empty($enum)) {
                return [$const];
            } elseif (in_array($const, $enum)) {
                $this->addWarning(
                    'enum is redundant when const is specified',
                    Warning::REDUNDANT,
                );
                return [$const];
            } else {
                throw SchemaShouldBeBoolean::alwaysFalse(
                    $this->getIdentifier(),
                    'const is not contained within enum, '
                    . 'one or the other will always fail',
                );
            }
        }

        if ($enum === null) {
            return null;
        }

        if ($enum === []) {
            throw SchemaShouldBeBoolean::alwaysFalse(
                $this->getIdentifier(),
                'enum does not contain any values',
            );
        }

        if ($types === []) {
            return array_values($enum);
        }

        $enumContainsValidValue = false;
        foreach ($enum as $value) {
            foreach ($types as $type) {
                if ($type->doesValueMatchType($value)) {
                    $enumContainsValidValue = true;
                } else {
                    $this->addWarning(
                        "$value does not match allowed types",
                        Warning::MISLEADING,
                    );
                }
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

        foreach ($types as $type) {
            if ($type->doesValueMatchType($default)) {
                return $default;
            }
        }
        throw InvalidOpenAPI::defaultMustConformToType($this->getIdentifier());
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

    private function validateMinimum(
        float|int|null $minimum,
        bool|float|int|null $exclusiveMinimum,
    ): Limit|null {
        if (is_bool($exclusiveMinimum)) {
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31(
                $this->getIdentifier(),
                'exclusiveMinimum',
            );
        }

        if (isset($exclusiveMinimum) && isset($minimum)) {
            $this->addWarning(
                'Having both minimum and exclusiveMinimum is redundant, '
                . 'only the stricter one will ever apply',
                Warning::REDUNDANT,
            );
            return $exclusiveMinimum >= $minimum ?
                new Limit($exclusiveMinimum, true) :
                new Limit($minimum, false);
        } elseif (isset($exclusiveMinimum)) {
            return new Limit($exclusiveMinimum, true);
        } elseif (isset($minimum)) {
            return new Limit($minimum, false);
        } else {
            return null;
        }
    }

    private function validateMaximum(
        float|int|null $maximum,
        bool|float|int|null $exclusiveMaximum,
    ): Limit|null {
        if (is_bool($exclusiveMaximum)) {
            throw InvalidOpenAPI::boolExclusiveMinMaxIn31(
                $this->getIdentifier(),
                'exclusiveMaximum',
            );
        }

        if (isset($exclusiveMaximum) && isset($maximum)) {
            $this->addWarning(
                'Having both maximum and exclusiveMaximum is redundant, '
                . 'only the stricter one will ever apply',
                Warning::REDUNDANT,
            );
            return $exclusiveMaximum <= $maximum ?
                new Limit($exclusiveMaximum, true) :
                new Limit($maximum, false);
        } elseif (isset($exclusiveMaximum)) {
            return new Limit($exclusiveMaximum, true);
        } elseif (isset($maximum)) {
            return new Limit($maximum, false);
        } else {
            return null;
        }
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
