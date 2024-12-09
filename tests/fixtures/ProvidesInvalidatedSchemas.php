<?php

declare(strict_types=1);



namespace Membrane\OpenAPIReader\Tests\Fixtures;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Value;

final class ProvidesInvalidatedSchemas
{
    /**
     * @return Generator<array{
     *     0:InvalidOpenAPI,
     *     1:Identifier,
     *     2:Partial\Schema,
     * }>
     */
    public static function forV3X(): Generator
    {
        $identifier = new Identifier('sut');

        yield 'invalid type' => [
            InvalidOpenAPI::invalidType($identifier, 'invalid'),
            $identifier,
            new Partial\Schema(type: 'invalid'),
        ];

        yield 'properties without string keys' => [
            InvalidOpenAPI::mustHaveStringKeys($identifier, 'properties'),
            $identifier,
            new Partial\Schema(properties: [new Partial\Schema()]),
        ];

        yield 'negative maxLength' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger($identifier, 'maxLength'),
            $identifier,
            new Partial\Schema(maxLength: -1),
        ];

        yield 'negative maxItems' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger($identifier, 'maxItems'),
            $identifier,
            new Partial\Schema(maxItems: -1),
        ];

        yield 'negative maxProperties' => [
            InvalidOpenAPI::keywordMustBeNonNegativeInteger($identifier, 'maxProperties'),
            $identifier,
            new Partial\Schema(maxProperties: -1),
        ];

        yield 'zero multipleOf' => [
            InvalidOpenAPI::keywordCannotBeZero($identifier, 'multipleOf'),
            $identifier,
            new Partial\Schema(multipleOf: 0),
        ];

        yield 'default does not conform to type' => [
            InvalidOpenAPI::defaultMustConformToType($identifier),
            $identifier,
            new Partial\Schema(type: 'string', default: new Value(1)),
        ];
    }

    /**
     * @return Generator<array{
     *     0:InvalidOpenAPI,
     *     1:Identifier,
     *     2:Partial\Schema,
     * }>
     */
    public static function forV30(): Generator
    {
        $identifier = new Identifier('sut');

        yield 'numeric exclusiveMaximum' => [
            InvalidOpenAPI::numericExclusiveMinMaxIn30($identifier, 'exclusiveMaximum'),
            $identifier,
            new Partial\Schema(exclusiveMaximum: 5),
        ];

        yield 'numeric exclusiveMinimum' => [
            InvalidOpenAPI::numericExclusiveMinMaxIn30($identifier, 'exclusiveMinimum'),
            $identifier,
            new Partial\Schema(exclusiveMinimum: 5),
        ];
    }

    /**
     * @return Generator<array{
     *     0:InvalidOpenAPI,
     *     1:Identifier,
     *     2:Partial\Schema,
     * }>
     */
    public static function forV31(): Generator
    {
        $identifier = new Identifier('sut');

        yield 'numeric exclusiveMaximum' => [
            InvalidOpenAPI::boolExclusiveMinMaxIn31($identifier, 'exclusiveMaximum'),
            $identifier,
            new Partial\Schema(exclusiveMaximum: true),
        ];

        yield 'numeric exclusiveMinimum' => [
            InvalidOpenAPI::boolExclusiveMinMaxIn31($identifier, 'exclusiveMinimum'),
            $identifier,
            new Partial\Schema(exclusiveMinimum: true),
        ];
    }
}
