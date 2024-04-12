<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\MediaType;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
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
#[UsesClass(Type::class)]
#[UsesClass(Style::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
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
    public function itCanDefaultStylePerLocation(
        Style $expected,
        In $in,
    ): void {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(in: $in->value, style: null)
        );

        self::assertSame($expected, $sut->style);
    }

    #[Test, DataProvider('provideStyles')]
    public function itCanDefaultExplodePerStyle(In $in, Style $style): void
    {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(
                in: $in->value,
                style: $style->value,
                explode: null
            )
        );

        self::assertSame($style === Style::Form, $sut->explode);
    }

    #[Test, DataProvider('provideLocations')]
    public function itCanDefaultRequiredIfOptional(In $in): void
    {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(in: $in->value, required: null)
        );

        self::assertFalse($sut->required);
    }

    #[Test, DataProvider('provideRequired')]
    public function itWillTakeRequiredIfSpecified(bool $required): void
    {
        $sut = new Parameter(
            new Identifier('test'),
            PartialHelper::createParameter(in: 'query', required: $required)
        );

        self::assertSame($required, $sut->required);
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

    #[Test, DataProvider('provideStylesThatMayBeSuitable')]
    public function itWarnsAgainstUnsuitableStyles(
        bool $expected,
        Partial\Parameter $parameter,
    ): void {
        $sut = new Parameter(new Identifier(''), $parameter);

        self::assertSame(
            $expected,
            $sut->getWarnings()->hasWarningCode(Warning::UNSUITABLE_STYLE)
        );
    }

    #[Test]
    #[DataProvider('provideParametersThatAreNotInQuery')]
    #[DataProvider('provideParametersWithDifferentStyles')]
    #[DataProvider('provideParametersThatMustBePrimitiveType')]
    #[DataProvider('provideParametersThatConflict')]
    public function itChecksIfItCanConflict(
        bool $expected,
        Partial\Parameter $parameter,
        Partial\Parameter $other,
    ): void {
        $sut = new Parameter(new Identifier(''), $parameter);
        $otherSUT = new Parameter(new Identifier(''), $other);

        self::assertSame($expected, $sut->canConflict($otherSUT));
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

    public static function provideStyles(): Generator
    {
        yield 'matrix' => [In::Path, Style::Matrix];
        yield 'label' => [In::Path, Style::Label];
        yield 'form' => [In::Query, Style::Form];
        yield 'simple' => [In::Path, Style::Simple];
        yield 'spaceDelimited' => [In::Query, Style::SpaceDelimited];
        yield 'pipeDelimited' => [In::Query, Style::PipeDelimited];
        yield 'deepObject' => [In::Query, Style::DeepObject];
    }

    public static function provideLocations(): Generator
    {
        yield 'query' => [In::Query];
        yield 'header' => [In::Header];
        yield 'cookie' => [In::Cookie];
    }

    public static function provideRequired(): Generator
    {
        yield 'required' => [true];
        yield 'not required' => [false];
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
        yield 'similar - "Äöü" and "äöü"' => [true, 'Äöü', 'äöü'];
        yield 'not similar - "äöü" and "param"' => [false, 'äöü', 'param'];
        yield 'not similar - "param" and "äöü"' => [false, 'param', 'äöü'];
    }

    /** @return Generator<array{ 0: bool, 1: Partial\Parameter }> */
    public static function provideStylesThatMayBeSuitable(): Generator
    {
        foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $type) {
            foreach (Style::cases() as $style) {
                yield "style:$style->value, type:$type->value" => [
                    !$style->isSuitableFor($type),
                    PartialHelper::createParameter(
                        in: array_values(array_filter(
                            In::cases(),
                            fn($in) => $style->isAllowed($in)
                        ))[0]->value,
                        style: $style->value,
                        schema: PartialHelper::createSchema(type: $type->value)
                    )
                ];
            }
        }
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Parameter,
     *     2: Partial\Parameter,
     * }>
     */
    public static function provideParametersThatAreNotInQuery(): Generator
    {
        $availableStyles = fn($in) => array_filter(
            Style::cases(),
            fn($s) => $s->isAllowed($in)
        );

        foreach (array_filter(In::cases(), fn($i) => $i !== In::Query) as $in) {
            foreach ($availableStyles($in) as $style) {
                $parameter = fn(bool $explode) => PartialHelper::createParameter(
                    in: $in->value,
                    style: $style->value,
                    explode: $explode
                );

                yield "style:$style->value, in:$in->value, explode:true" => [
                    false,
                    $parameter(true),
                    $parameter(true),
                ];

                yield "style:$style->value, in:$in->value, explode:false" => [
                    false,
                    $parameter(false),
                    $parameter(false),
                ];
            }
        }
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Parameter,
     *     2: Partial\Parameter,
     * }>
     */
    public static function provideParametersWithDifferentStyles(): Generator
    {
        $availableStyles =  array_filter(
            Style::cases(),
            fn($s) => $s->isAllowed(In::Query)
        );

        while (count($availableStyles) > 1) {
            $style = array_pop($availableStyles);
            foreach ($availableStyles as $other) {
                yield "style:$style->value and other style:$other->value" => [
                    false,
                    PartialHelper::createParameter(
                        in: In::Query->value,
                        style: $style->value,
                        explode: true,
                    ),
                    PartialHelper::createParameter(
                        in: In::Query->value,
                        style: $other->value,
                        explode: true,
                    ),
                ];
            }
        }
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Parameter,
     *     2: Partial\Parameter,
     * }>
     */
    public static function provideParametersThatConflict(): Generator
    {
        $dataSet = fn(Style $style, bool $explode, Type $type) => [
            true,
            PartialHelper::createParameter(
                in: In::Query->value,
                style: $style->value,
                explode: $explode,
                schema: PartialHelper::createSchema(type: $type->value)
            ),
            PartialHelper::createParameter(
                in: In::Query->value,
                style: $style->value,
                explode: $explode,
                schema: PartialHelper::createSchema(type: $type->value)
            ),
        ];

        yield 'style:form and explode:true for object data types' =>
        $dataSet(Style::Form, true, Type::Object);


        yield 'style:pipeDelimited for array data types' =>
        $dataSet(Style::PipeDelimited, false, Type::Array);

        yield 'style:pipeDelimited for object data types' =>
        $dataSet(Style::PipeDelimited, false, Type::Object);

        yield 'style:spaceDelimited for array data types' =>
        $dataSet(Style::SpaceDelimited, false, Type::Array);

        yield 'style:spaceDelimited for object data types' =>
        $dataSet(Style::SpaceDelimited, false, Type::Object);
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Partial\Parameter,
     *     2: Partial\Parameter,
     * }>
     */
    public static function provideParametersThatMustBePrimitiveType(): Generator
    {
        foreach (self::provideParametersThatConflict() as $case => $dataSet) {
            $dataSet[1]->schema = PartialHelper::createSchema(type: Type::Integer->value);
            $dataSet[2]->schema = PartialHelper::createSchema(type: Type::Integer->value);
            
            yield $case => [
                false,
                $dataSet[1],
                $dataSet[2],
            ];
        }
    }
}
