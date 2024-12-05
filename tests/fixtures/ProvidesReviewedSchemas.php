<?php

declare(strict_types=1);



namespace Membrane\OpenAPIReader\Tests\Fixtures;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class ProvidesReviewedSchemas
{
    /**
     * @return Generator<array{
     *     0:Partial\Schema,
     *     1:Warning[],
     *     2:string,
     *     3:mixed
     * }>
     */
    public static function provideV3xReviews(): Generator
    {
        yield 'null allOf' => [
            new Partial\Schema(allOf: null),
            [],
            'allOf',
            [],
        ];

        yield 'null anyOf' => [
            new Partial\Schema(anyOf: null),
            [],
            'anyOf',
            [],
        ];

        yield 'null oneOf' => [
            new Partial\Schema(oneOf: null),
            [],
            'oneOf',
            [],
        ];

        yield 'empty allOf' => [
            new Partial\Schema(allOf: []),
            [new Warning('allOf must not be empty', Warning::INVALID)],
            'allOf',
            [],
        ];

        yield 'empty anyOf' => [
            new Partial\Schema(anyOf: []),
            [new Warning('anyOf must not be empty', Warning::INVALID)],
            'anyOf',
            [],
        ];

        yield 'empty oneOf' => [
            new Partial\Schema(oneOf: []),
            [new Warning('oneOf must not be empty', Warning::INVALID)],
            'oneOf',
            [],
        ];

        yield 'negative minLength' => [
            new Partial\Schema(minLength: -1),
            [new Warning('minLength must not be negative', Warning::INVALID)],
            'minLength',
            0,
        ];

        yield 'negative minItems' => [
            new Partial\Schema(minItems: -1),
            [new Warning('minItems must not be negative', Warning::INVALID)],
            'minItems',
            0,
        ];

        yield 'negative minProperties' => [
            new Partial\Schema(minProperties: -1),
            [new Warning('minProperties must not be negative', Warning::INVALID)],
            'minProperties',
            0,
        ];
    }

    /**
     * @return Generator<array{
     *     0:Partial\Schema,
     *     1:Warning,
     *     2:string,
     *     3: mixed,
     * }>
     */
    public static function provideV30Reviews(): Generator
    {
        yield 'empty required' => [
            new Partial\Schema(required: []),
            [new Warning('required must not be empty', Warning::INVALID)],
            'required',
            [],
        ];

        yield 'required contains duplicates' => [
            new Partial\Schema(required: ['id', 'id']),
            [new Warning('required must not contain duplicates', Warning::INVALID)],
            'required',
            ['id'],
        ];
    }
}
