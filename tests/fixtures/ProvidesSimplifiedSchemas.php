<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Limit;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Value;

final class ProvidesSimplifiedSchemas
{
    /**
     * @return Generator<array{
     *     0:Partial\Schema,
     *     1:string,
     *     2:mixed
     * }>
     */
    public static function forV3X(): Generator
    {
        yield 'allOf: null becomes array' => [new Partial\Schema(allOf: null), 'allOf', []];
        yield 'anyOf: null becomes array' => [new Partial\Schema(anyOf: null), 'anyOf', []];
        yield 'oneOf: null becomes array' => [new Partial\Schema(oneOf: null), 'oneOf', []];

        yield 'minLength: negatives become zero' => [new Partial\Schema(minLength: -1), 'minLength', 0];
        yield 'minItems: negatives become zero' => [new Partial\Schema(minItems: -1), 'minItems', 0];
        yield 'minProperties: negatives become zero' => [new Partial\Schema(minProperties: -1), 'minProperties', 0];

        yield 'required: null becomes array' => [new Partial\Schema(required: null), 'required', []];
        yield 'required: duplicates are removed' => [new Partial\Schema(required: ['id', 'id']), 'required', ['id']];
    }

    /**
     * @return Generator<array{
     *     0:Partial\Schema,
     *     1:string,
     *     2:mixed
     * }>
     */
    public static function forV30(): Generator
    {
        yield 'minimum combined: inclusive 1' => [
            new Partial\Schema(minimum: 1, exclusiveMinimum: false),
            'minimum',
            new Limit(1, false),
        ];
        yield 'minimum combined: exclusive 1' => [
            new Partial\Schema(minimum: 1, exclusiveMinimum: true),
            'minimum',
            new Limit(1, true),
        ];
        yield 'maximum combined: inclusive 1' => [
            new Partial\Schema(maximum: 1, exclusiveMaximum: false),
            'maximum',
            new Limit(1, false),
        ];
        yield 'maximum combined: exclusive 1' => [
            new Partial\Schema(maximum: 1, exclusiveMaximum: true),
            'maximum',
            new Limit(1, true),
        ];
    }

    /**
     * @return Generator<array{
     *     0:Partial\Schema,
     *     1:string,
     *     2:mixed
     * }>
     */
    public static function forV31(): Generator
    {
        yield 'exclusiveMinimum:5 overrides minimum:1' => [
            new Partial\Schema(minimum: 1, exclusiveMinimum: 5),
            'minimum',
            new Limit(5, true),
        ];
        yield 'exclusiveMinimum:1 overrides minimum:1' => [
            new Partial\Schema(minimum: 1, exclusiveMinimum: 1),
            'minimum',
            new Limit(1, true),
        ];
        yield 'minimum:5 overrides exclusiveMinimum:1' => [
            new Partial\Schema(minimum: 5, exclusiveMinimum: 1),
            'minimum',
            new Limit(5, false),
        ];

        yield 'exclusiveMaximum:1 overrides maximum:5' => [
            new Partial\Schema(maximum: 5, exclusiveMaximum: 1),
            'maximum',
            new Limit(1, true),
        ];
        yield 'exclusiveMaximum:1 overrides maximum:1' => [
            new Partial\Schema(maximum: 1, exclusiveMaximum: 1),
            'maximum',
            new Limit(1, true),
        ];
        yield 'maximum:1 overrides exclusiveMaximum:5' => [
            new Partial\Schema(maximum: 1, exclusiveMaximum: 5),
            'maximum',
            new Limit(1, false),
        ];

        yield 'const overrides enum' => [
            new Partial\Schema(const: new Value(3), enum: [new Value(1), new Value(2), new Value(3)]),
            'enum',
            [new Value(3)],
        ];
    }
}
