<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Partial;

use Membrane\OpenAPIReader\ValueObject\Value;

final class Schema
{
    /**
     * @param array<string>|string|null $type
     * @param ?self[] $allOf
     * @param ?self[] $anyOf
     * @param ?self[] $oneOf
     */
    public function __construct(
        /**
         * Keywords for any type
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#autoid-11
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-any
         */
        /** @var string[]|string|null */
        public array|string|null $type = null,
        /** @var array<Value>|null */
        public array|null $enum = null,
        public Value|null $const = null,
        public Value|null $default = null,
        /**
         * 3.0 keywords that are extensions to the spec
         * https://github.com/OAI/OpenAPI-Specification/blob/3.1.1/versions/3.0.4.md#fixed-fields-21
         */
        public bool $nullable = false,
        /**
         * Keywords for numeric type
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#autoid-11
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-num
         */
        public float|int|null $multipleOf = null,
        public bool|float|int|null $exclusiveMaximum = null,
        public bool|float|int|null $exclusiveMinimum = null,
        public float|int|null $maximum = null,
        public float|int|null $minimum = null,
        /**
         * Keywords for string type
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#autoid-11
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-str
         */
        public int|null $maxLength = null,
        public int $minLength = 0,
        public string|null $pattern = null,
        /**
         * Keywords for array type
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#autoid-11
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-arr
         */
        public int|null $maxItems = null,
        public int $minItems = 0,
        public bool $uniqueItems = false,
        public int|null $maxContains = null,
        public int|null $minContains = null,
        /**
         * Keywords for object type
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#autoid-11
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-validation#name-validation-keywords-for-obj
         */
        public int|null $maxProperties = null,
        public int $minProperties = 0,
        /** @var array<string>|null */
        public array|null $required = null,
        /** @var array<string,array<string>>|null */
        public array|null $dependentRequired = null,
        /**
         * Keywords for applying subschemas with logic
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.22
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-core#name-keywords-for-applying-subsch
         */
        /** @var array<Schema>|null */
        public array|null $allOf = null,
        /** @var array<Schema>|null */
        public array|null $anyOf = null,
        /** @var array<Schema>|null */
        public array|null $oneOf = null,
        public bool|Schema|null $not = null,
        /**
         * Keywords for applying subschemas conditionally
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.22
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-core#name-keywords-for-applying-subsche
         */
        public Schema|null $if = null,
        public Schema|null $then = null,
        public Schema|null $else = null,
        /** @var array<string,Schema>|null */
        public array|null $dependentSchemas = null,
        /**
         * Keywords for applying subschemas to arrays
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.22
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-core#name-keywords-for-applying-subschema
         */
        /** @var array<Schema> */
        public array $prefixItems = [],
        public Schema|null $items = null,
        public Schema|null $contains = null,
        /**
         * Keywords for applying subschemas to arrays
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.22
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-core#name-keywords-for-applying-subschemas
         */
        /** @var array<string, Schema> */
        public array $properties = [],
        /** @var array<string, Schema> */
        public array $patternProperties = [],
        public bool|Schema $additionalProperties = true,
        public bool|Schema $propertyNames = true,
        /**
         * Keywords that are exceptions to the usual "keyword independence"
         * 3.0 https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.22
         * 3.1 https://json-schema.org/draft/2020-12/json-schema-core#name-keyword-independence-2
         */
        public bool|Schema $unevaluatedItems = true,
        public bool|Schema $unevaluatedProperties = true,
        /**
         * Keywords that MAY provide additional validation, depending on tool
         * https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-7
         */
        public string|null $format = null,
        /**
         * Keywords that provide additional metadata
         * https://json-schema.org/draft/2020-12/json-schema-validation#section-9
         */
        public string|null $title = null,
        public string|null $description = null,
    ) {
    }
}
