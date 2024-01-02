<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Warnings::class)]
#[CoversClass(Warning::class)]
#[UsesClass(Identifier::class)]
class WarningsTest extends TestCase
{
    #[Test, DataProvider('provideWarnings')]
    public function itCanGetAllWarnings(Warning ...$warnings): void
    {
        $sut = new Warnings(new Identifier('test'), ...$warnings);

        self::assertSame($warnings, $sut->all());
    }

    #[Test, DataProvider('provideWarnings')]
    public function itCanAddAWarning(Warning ...$warnings): void
    {
        $newWarning = new Warning('this should be added', 'add-warning');
        $expected = [...$warnings, $newWarning];

        $sut = new Warnings(new Identifier('test'), ...$warnings);

        $sut->add($newWarning->message, $newWarning->code);

        self::assertEquals($expected, $sut->all());
    }


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
}
