<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

//todo test findByWarningCodes

#[CoversClass(Warnings::class)]
#[CoversClass(Warning::class)]
#[UsesClass(Identifier::class)]
class WarningsTest extends TestCase
{
    #[Test]
    public function itGetsIdentifier(): void
    {
        $expected = new Identifier('test');
        $sut = new Warnings($expected);

        self::assertEquals($expected, $sut->getIdentifier());
    }

    #[Test, DataProvider('provideWarnings')]
    public function itAddsWarnings(Warning ...$warnings): void
    {
        $newWarning = new Warning('this should be added', 'add-warning');
        $expected = [...$warnings, $newWarning];

        $sut = new Warnings(new Identifier('test'), ...$warnings);

        $sut->add($newWarning->message, $newWarning->code);

        self::assertEquals($expected, $sut->all());
    }

    #[Test, DataProvider('provideWarnings')]
    public function itGetsAllWarnings(Warning ...$warnings): void
    {
        $sut = new Warnings(new Identifier('test'), ...$warnings);

        self::assertSame($warnings, $sut->all());
    }

    /**
     * @param Warning[] $expected
     * @param Warning[] $warnings
     * @param string[] $codes
     */
    #[Test, DataProvider('provideWarningsToFind')]
    public function itFindsWarningsByCodes(
        array $expected,
        array $warnings,
        array $codes,
    ): void {
        $sut = new Warnings(new Identifier('test'), ...$warnings);

        self::assertEquals($expected, $sut->findByWarningCodes(...$codes));
    }

    #[Test, DataProvider('provideWarnings')]
    public function itChecksItHasWarnings(Warning ...$warnings): void
    {
        $sut = new Warnings(new Identifier('test'), ...$warnings);

        self::assertSame(!empty($warnings), $sut->hasWarnings());
    }

    #[Test, DataProvider('provideCodesToCheck')]
    public function itChecksItHasWarningCodes(
        bool $expected,
        array $codes,
        array $warnings,
    ): void {
        $sut = new Warnings(new Identifier('test'), ...$warnings);

        self::assertSame($expected, $sut->hasWarningCodes(...$codes));
    }

    /**
     * @return Generator<string,Warning[]>
     */
    public static function provideWarnings(): Generator
    {
        yield 'no warnings' => [];
        yield 'one warning' => [new Warning('think fast!', 'too-slow')];
        yield 'three warnings' => [
            new Warning('think fast!', 'too-slow'),
            new Warning('watch out!', 'clock-in'),
            new Warning('duck!', 'quack'),
        ];
    }

    /**
     * @return Generator<array{
     *     0: Warning[],
     *     1: Warning[],
     *     2: string[],
     * }>
     */
    public static function provideWarningsToFind(): Generator
    {
        foreach (self::provideWarnings() as $case => $warnings) {
            yield "$case, single, not contained, code" => [
                [],
                $warnings,
                ['This code is most definitely not contained anywhere'],
            ];

            yield "$case, multiple, not contained, codes" => [
                [],
                $warnings,
                [
                    'This code is most definitely not contained anywhere',
                    'This code is almost certainly not contained anywhere',
                    'This code is guaranteed not to be contained somewhere',
                ],
            ];

            if (!empty($warnings)) {
                $codes = array_map(fn($w) => $w->code, $warnings);

                yield "$case, single, contained, code" => [
                    [$warnings[0]],
                    $warnings,
                    [$codes[0]],
                ];

                yield "$case, one contained code, duplicated three times" => [
                    [$warnings[0]],
                    $warnings,
                    [$codes[0], $codes[0], $codes[0]],
                ];

                yield "$case, one contained code, one that is not" => [
                    [$warnings[0]],
                    $warnings,
                    ['This code is certainly not contained', $codes[0]],
                ];

                if (count($warnings) > 1) {
                    yield "$case, all contained codes" => [
                        $warnings,
                        $warnings,
                        $codes,
                    ];

                    $mixedCodes = [];
                    foreach ($codes as $code) {
                        $mixedCodes[] = 'This code is certainly not contained';
                        $mixedCodes[] = $code;
                    }

                    yield "$case, all contained codes, several that aren't" => [
                        $warnings,
                        $warnings,
                        $mixedCodes,
                    ];
                }
            }
        }
    }


    public static function provideCodesToCheck(): Generator
    {
        foreach (self::provideWarnings() as $case => $warnings) {
            yield "$case, single, not contained, code" => [
                false,
                ['This code is most definitely not contained anywhere'],
                $warnings
            ];

            yield "$case, multiple, not contained, codes" => [
                false,
                [
                    'This code is most definitely not contained anywhere',
                    'This code is almost certainly not contained anywhere',
                    'This code is guaranteed not to be contained somewhere',
                ],
                $warnings
            ];

            if (!empty($warnings)) {
                $codes = array_map(fn($w) => $w->code, $warnings);

                yield "$case, single, contained, code" => [
                    true,
                    [$codes[0]],
                    $warnings
                ];

                yield "$case, one contained code, duplicated three times" => [
                    true,
                    [$codes[0], $codes[0], $codes[0]],
                    $warnings
                ];

                yield "$case, one contained code, one that is not" => [
                    true,
                    ['This code is certainly not contained', $codes[0]],
                    $warnings
                ];

                if (count($warnings) > 1) {
                    yield "$case, all contained codes" => [
                        true,
                        $codes,
                        $warnings
                    ];

                    $mixedCodes = [];
                    foreach ($codes as $code) {
                        $mixedCodes[] = 'This code is certainly not contained';
                        $mixedCodes[] = $code;
                    }

                    yield "$case, all contained codes, several that aren't" => [
                        true,
                        $mixedCodes,
                        $warnings,
                    ];
                }
            }
        }
    }
}
