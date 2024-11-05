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
use Membrane\OpenAPIReader\ValueObject\Value;

final class Schema extends Validated
{
    /**
     * If specified, this keyword's value  MUST be one of the following:
     * "boolean", "object", "array", "number", "string", or "integer"
     */
    public readonly Type|null $type;
    public readonly bool $nullable;
    /** @var array<Value>|null */
    public readonly array|null $enum;
    public readonly Value|null $default;

    public readonly float|int|null $multipleOf;
    public readonly float|int|null $maximum;
    public readonly bool $exclusiveMaximum;
    public readonly float|int|null $minimum;
    public readonly bool $exclusiveMinimum;

    public readonly int|null $maxLength;
    public readonly int|null $minLength;
    public readonly string|null $pattern;

    public readonly int|null $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public readonly int|null $maxProperties;
    public readonly int $minProperties;
    /** @var array<string>|null  */
    public readonly array|null $required;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var array<Schema>|null
     */
    public readonly array|null $allOf;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var array<Schema>|null
     */
    public readonly ?array $anyOf;

    /**
     * If specified, this keyword's value MUST be a non-empty array.
     * @var array<Schema>|null
     */
    public readonly array|null $oneOf;

    public readonly Schema|null $not;

    /**
     * @var Type[]
     */
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
        $this->minLength = $this->validateNonNegativeInteger('minLength', $schema->minLength);
        $this->pattern = $schema->pattern;

        $this->maxItems = $this->validateNonNegativeInteger('maxItems', $schema->maxItems);
        $this->minItems = $this->validateNonNegativeInteger('minItems', $schema->minItems) ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        $this->maxProperties = $schema->maxProperties;
        $this->minProperties = $this->validateNonNegativeInteger('minProperties', $schema->minProperties) ?? 0;
        $this->required = $this->validateRequired($schema->required);

        $this->allOf = $this->validateSubSchemas('allOf', $schema->allOf);
        $this->anyOf = $this->validateSubSchemas('anyOf', $schema->anyOf);
        $this->oneOf = $this->validateSubSchemas('oneOf', $schema->oneOf);
        $this->not = isset($schema->not) ?
            new Schema($this->getIdentifier()->append('not'), $schema->not) :
            null;

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
     * @return array<string>|null
     */
    private function validateRequired(array|null $value): array|null
    {
        if ($value === null) {
            return $value;
        }

        if ($value !== null && count($value) !== count(array_unique($value))) {
            throw InvalidOpenAPI::requiredMustBeUnique($this->getIdentifier());
        }

        return $value;
    }
}
