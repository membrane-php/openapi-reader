<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\In;
use Membrane\OpenAPIReader\Style;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\MediaType;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parameter::class)]
#[CoversClass(Partial\Parameter::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
class ParameterTest extends TestCase
{
    #[Test, DataProvider('provideInvalidPartialParameters')]
    public function itValidatesParameters(
        Identifier $parentIdentifier,
        InvalidOpenAPI $expected,
        Partial\Parameter $partialParameter
    ): void {
        self::expectExceptionObject($expected);

        new Parameter($parentIdentifier, $partialParameter);
    }

    #[Test, DataProvider('provideParametersWithSchemasXorMediaType')]
    #[TestDox('Its Schema may be defined in two different locations, a convenience method can fetch it for you.')]
    public function itCanGetItsSchema(
        Schema $expected,
        Identifier $identifier,
        Partial\Parameter $partialParameter
    ): void {
        $sut = new Parameter($identifier, $partialParameter);

        self::assertEquals($expected, $sut->getSchema());
    }

    #[Test, DataProvider('provideParametersWithOrWithoutMediaTypes')]
    #[TestDox('A convenience method exists to check if it has a media type')]
    public function itCanTellIfItHasAMediaType(
        bool $expected,
        ?string $expectedMediaType,
        Partial\Parameter $partialParameter
    ): void {
        $sut = new Parameter(new Identifier('test'), $partialParameter);

        self::assertSame($expected, $sut->hasMediaType());

        self::assertSame($expectedMediaType, $sut->getMediaType());
    }

    #[Test]
    #[DataProvider('provideValidStylesPerLocation')]
    public function itCanHaveAnyValidStylePerLocation(
        Style $style,
        In $in,
    ): void {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(in: $in->value, style: $style->value)
        );

        self::assertSame($style, $sut->style);
        self::assertSame($in, $sut->in);
    }

    #[Test]
    #[DataProvider('provideDefaultStylesPerLocation')]
    public function itWillDefaultStylePerLocation(
        Style $expected,
        In $in,
    ): void {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(in: $in->value)
        );

        self::assertSame($expected, $sut->style);
    }

    #[Test, DataProvider('provideParametersThatMayBeIdentical')]
    #[TestDox('Parameter "name" and "in" must be identical')]
    public function itChecksIfIdentical(
        bool $expected,
        Partial\Parameter $parameter,
        Partial\Parameter $otherParameter,
    ): void {
        $sut = new Parameter(new Identifier(''), $parameter);
        $otherSUT = new Parameter(new Identifier(''), $otherParameter);

        self::assertSame($expected, $sut->isIdentical($otherSUT));
    }

    #[Test, DataProvider('provideNamesThatMayBeSimilar')]
    #[TestDox('"name" MUST NOT be identical, unless compared by case-insensitive comparison')]
    public function itChecksIfNameIsSimilar(
        bool $expected,
        string $name,
        string $other,
    ): void {
        $sut = new Parameter(
            new Identifier(''),
            PartialHelper::createParameter(name: $name)
        );
        $otherSUT = new Parameter(
            new Identifier(''),
            PartialHelper::createParameter(name: $other)
        );

        self::assertSame($expected, $sut->isSimilar($otherSUT));
    }

    public static function provideInvalidPartialParameters(): Generator
    {
        $parentIdentifier = new Identifier('test');
        $name = 'test-param';
        $in = 'path';
        $identifier = $parentIdentifier->append("$name($in)");

        $case = fn($exception, $data) => [
            $parentIdentifier,
            $exception,
            PartialHelper::createParameter(...array_merge(
                ['name' => $name, 'in' => $in],
                $data
            )),
        ];

        yield 'missing "name"' => $case(
            InvalidOpenAPI::parameterMissingName($parentIdentifier),
            ['name' => null],
        );

        yield 'missing "in"' => $case(
            InvalidOpenAPI::parameterMissingLocation($parentIdentifier),
            ['in' => null],
        );

        yield 'invalid "in"' => $case(
            InvalidOpenAPI::parameterInvalidLocation($parentIdentifier),
            ['in' => 'Wonderland']
        );

        yield 'missing "required" when "in":"path"' => $case(
            InvalidOpenAPI::parameterMissingRequired($identifier),
            ['required' => null],
        );

        yield 'invalid "style"' => $case(
            InvalidOpenAPI::parameterInvalidStyle($identifier),
            ['style' => 'Fabulous!']
        );

        $incompatibleStylesPerLocation = [
            'matrix' => ['query', 'header', 'cookie'],
            'label' => ['query', 'header', 'cookie'],
            'form' => ['path', 'header'],
            'simple' => ['query', 'cookie'],
            'spaceDelimited' => ['path', 'header', 'cookie'],
            'pipeDelimited' => ['path', 'header', 'cookie'],
            'deepObject' => ['path', 'header', 'cookie']
        ];

        foreach ($incompatibleStylesPerLocation as $style => $locations) {
            foreach ($locations as $location) {
                yield "incompatible $style for $location" => $case(
                    InvalidOpenAPI::parameterIncompatibleStyle($parentIdentifier->append($name, $location)),
                    ['style' => $style, 'in' => $location]
                );
            }
        }

        $schemaXorContentCases = [
            'no schema nor content' => ['schema' => null, 'content' => []],
            'schema and content with schema' => [
                'schema' => PartialHelper::createSchema(),
                'content' => [
                    PartialHelper::createMediaType(
                        schema: PartialHelper::createSchema()
                    ),
                ],
            ],
            'no schema, has content, but content does not have a schema' => [
                'schema' => null,
                'content' => [PartialHelper::createMediaType(schema: null)],
            ],
        ];

        foreach ($schemaXorContentCases as $schemaXorContentCase => $data) {
            yield $schemaXorContentCase => $case(
                InvalidOpenAPI::mustHaveSchemaXorContent($name),
                $data,
            );
        }

        yield 'content with more than one mediaType' => $case(
            InvalidOpenAPI::parameterContentCanOnlyHaveOneEntry($identifier),
            [
                'schema' => null,
                'content' => [
                    PartialHelper::createMediaType(
                        mediaType: 'application/json',
                        schema: PartialHelper::createSchema()
                    ),
                    PartialHelper::createMediaType(
                        mediaType: 'application/pdf',
                        schema: PartialHelper::createSchema()
                    )
                ]
            ]
        );

        yield 'content has schema, but no mediaType specified' => $case(
            InvalidOpenAPI::contentMissingMediaType($identifier),
            [
                'schema' => null,
                'content' => [PartialHelper::createMediaType(
                    mediaType: null,
                    schema: PartialHelper::createSchema()
                )]
            ]
        );
    }

    public static function provideParametersWithSchemasXorMediaType(): array
    {
        $schema = PartialHelper::createSchema(type: 'boolean');
        $parentIdentifier = new Identifier('test');
        $name = 'param';
        $in = 'path';
        $identifier = $parentIdentifier->append($name, $in);

        $case = fn($schemaIdentifier, $data) => [
            new Schema($schemaIdentifier, $schema),
            $parentIdentifier,
            PartialHelper::createParameter(...array_merge(
                ['name' => $name, 'in' => $in],
                $data
            ))

        ];

        return [
            'with schema' => $case(
                $identifier->append('schema'),
                ['schema' => $schema, 'content' => []]
            ),
            'with media type' => $case(
                ($identifier->append('application/json'))->append('schema'),
                [
                    'schema' => null,
                    'content' => [PartialHelper::createMediaType(
                        mediaType: 'application/json',
                        schema: $schema
                    )],
                ]
            )
        ];
    }

    public static function provideParametersWithOrWithoutMediaTypes(): Generator
    {
        yield 'with schema' => [
            false,
            null,
            PartialHelper::createParameter(
                schema: PartialHelper::createSchema(),
                content: []
            )
        ];

        yield 'with media type' => [
            true,
            'application/json',
            PartialHelper::createParameter(
                schema: null,
                content: [PartialHelper::createMediaType(
                    mediaType: 'application/json',
                    schema: PartialHelper::createSchema()
                )]
            )
        ];
    }

    /**
     * @return Generator<array{
     *     0: Style,
     *     1: In,
     * }>
     */
    public static function provideValidStylesPerLocation(): Generator
    {
        yield 'matrix - path' => [Style::Matrix, In::Path];
        yield 'label - path' => [Style::Label, In::Path];
        yield 'simple - path' => [Style::Simple, In::Path];

        yield 'form - query' => [Style::Form, In::Query];
        yield 'spaceDelimited - query' => [Style::SpaceDelimited, In::Query];
        yield 'pipeDelimited - query' => [Style::PipeDelimited, In::Query];
        yield 'deepObject - query' => [Style::DeepObject, In::Query];

        yield 'simple - header' => [Style::Simple, In::Header];

        yield 'form - cookie' => [Style::Form, In::Cookie];
    }

    /**
     * @return Generator<array{
     *     0: Style,
     *     1: In,
     * }>
     */
    public static function provideDefaultStylesPerLocation(): Generator
    {
        yield 'simple - path' => [Style::Simple, In::Path];

        yield 'form - query' => [Style::Form, In::Query];

        yield 'simple - header' => [Style::Simple, In::Header];

        yield 'form - cookie' => [Style::Form, In::Cookie];
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Parameter,
     *     2: Partial\Parameter,
     * }>
     */
    public static function provideParametersThatMayBeIdentical(): Generator
    {
        $cases = [
            'identical name - "param"' => [true, 'param', 'param'],
            'identical name - "äöü"' => [true, 'äöü', 'äöü'],
            'similar names - "param" and "Param"' => [false, 'param', 'Param'],
            'similar names - "äöü" and "Äöü"' => [false, 'äöü', 'Äöü'],
            'not similar names - "äöü" and "param"' => [false, 'äöü', 'param'],
        ];

        foreach ($cases as $case => $data) {
            yield "$case with identical locations" => [
                $data[0],
                PartialHelper::createParameter(name: $data[1], in: 'path'),
                PartialHelper::createParameter(name: $data[2], in: 'path'),
            ];

            yield "$case with different locations" => [
                false,
                PartialHelper::createParameter(name: $data[1], in: 'path'),
                PartialHelper::createParameter(name: $data[2], in: 'query'),
            ];
        }
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: string,
     *     2: string,
     * }>
     */
    public static function provideNamesThatMayBeSimilar(): Generator
    {
        yield 'identical - "param"' => [false, 'param', 'param'];
        yield 'identical - "äöü"' => [false, 'äöü', 'äöü'];
        yield 'similar - "param" and "Param"' => [true, 'param', 'Param'];
        yield 'similar - "äöü" and "Äöü"' => [true, 'äöü', 'Äöü'];
        yield 'not similar - "äöü" and "param"' => [false, 'äöü', 'param'];
    }
}
