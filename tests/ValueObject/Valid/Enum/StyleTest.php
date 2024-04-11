<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\Enum;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Style::class)]
class StyleTest extends TestCase
{

    #[Test, DataProvider('provideDefaultStylesIn')]
    public function itGetsDefaultStyles(Style $expected, In $in): void
    {
        self::assertSame($expected, Style::defaultCaseIn($in));
    }

    /**
     * @param Style[] $expected
     */
    #[Test, DataProvider('provideStylesIn')]
    public function itGetsStylesIn(array $expected, In $in): void
    {
        self::assertSame($expected, Style::casesIn($in));
    }

    #[Test, DataProvider('provideDefaultExplodes')]
    public function itGetsExplodeDefault(bool $expected, Style $style): void
    {
        self::assertSame($expected, $style->defaultExplode());
    }

    /**
     * @return Generator<array{
     *     0: Style,
     *     1: In,
     * }>
     */
    public static function provideDefaultStylesIn(): Generator
    {
        yield 'path' => [Style::Simple, In::Path];
        yield 'query' => [Style::Form, In::Query];
        yield 'header' => [Style::Simple, In::Header];
        yield 'cookie' => [Style::Form, In::Cookie];
    }

    /**
     * @return Generator<array{
     *     0: Style[],
     *     1: In,
     * }>
     */
    public static function provideStylesIn(): Generator
    {
        yield 'path' => [
            [Style::Matrix, Style::Label, Style::Simple],
            In::Path
        ];
        yield 'query' => [
            [Style::Form, Style::SpaceDelimited, Style::PipeDelimited, Style::DeepObject],
            In::Query
        ];
        yield 'header' => [[Style::Simple], In::Header];
        yield 'cookie' => [[Style::Form], In::Cookie];
    }

    /**
     * @return Generator<array{
     *     0: bool,
     *     1: Style
     * }>
     */
    public static function provideDefaultExplodes(): Generator
    {
        foreach (Style::cases() as $case) {
            yield "$case->value" => [$case === Style::Form, $case];
        }
    }
}
