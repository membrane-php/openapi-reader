<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\Enum;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Style::class)]
#[UsesClass(Type::class)]
class StyleTest extends TestCase
{

    #[Test, DataProvider('provideDefaultStylesIn')]
    public function itGetsDefaultStyles(Style $expected, In $in): void
    {
        self::assertSame($expected, Style::default($in));
    }

    #[Test, DataProvider('provideStylesIn')]
    public function itGetsStylesIn(bool $expected, Style $style, In $in): void
    {
        self::assertSame($expected, $style->isAllowed($in));
    }

    #[Test, DataProvider('provideDefaultExplodes')]
    public function itGetsExplodeDefault(bool $expected, Style $style): void
    {
        self::assertSame($expected, $style->defaultExplode());
    }

    #[Test, DataProvider('provideStylesThatMayBeSuitable')]
    public function itKnowsIfItsSuitable(
        bool $expected,
        Style $style,
        Type $type,
    ): void {
        self::assertSame($expected, $style->isSuitableFor($type));
    }

    /** @return Generator<array{ 0: Style, 1: In }> */
    public static function provideDefaultStylesIn(): Generator
    {
        yield 'path' => [Style::Simple, In::Path];
        yield 'query' => [Style::Form, In::Query];
        yield 'header' => [Style::Simple, In::Header];
        yield 'cookie' => [Style::Form, In::Cookie];
    }

    /** @return Generator<array{ 0: bool, 1: Style, 2: In }> */
    public static function provideStylesIn(): Generator
    {
        foreach (Style::cases() as $style) {
            yield "style:$style->value, in:path" => [
                in_array($style, [Style::Matrix, Style::Label, Style::Simple]),
                $style,
                In::Path,
            ];

            yield "style:$style->value, in:query" => [
                in_array($style, [
                    Style::Form,
                    Style::SpaceDelimited,
                    Style::PipeDelimited,
                    Style::DeepObject
                ]),
                $style,
                In::Query,
            ];

            yield "style:$style->value, in:header" => [
                $style === Style::Simple,
                $style,
                In::Header,
            ];

            yield "style:$style->value, in:cookie" => [
                $style === Style::Form,
                $style,
                In::Cookie,
            ];
        }
    }

    /** @return Generator<array{ 0: bool, 1: Style }> */
    public static function provideDefaultExplodes(): Generator
    {
        foreach (Style::cases() as $case) {
            yield "$case->value" => [$case === Style::Form, $case];
        }
    }

    /** @return Generator<array{ 0: bool, 1: Style, 2: Type }> */
    public static function provideStylesThatMayBeSuitable(): Generator
    {
        foreach (Style::cases() as $style) {
            foreach (Type::cases() as $type) {
                yield "style:$style->value, type:$type->value" => [
                    match ($style) {
                        Style::SpaceDelimited,
                        Style::PipeDelimited => in_array(
                            $type,
                            [Type::Array, Type::Object]
                        ),
                        Style::DeepObject => $type === Type::Object,
                        default => true,
                    },
                    $style,
                    $type,
                ];
            }
        }
    }
}
