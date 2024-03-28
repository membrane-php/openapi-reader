<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid;

use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validated::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Warnings::class)]
#[UsesClass(Warning::class)]
class ValidatedTest extends TestCase
{
    #[Test]
    public function itHasAnIdentifier(): void
    {
        $expected = new Identifier('test');
        $sut = new class ($expected) extends Validated {
        };

        self::assertEquals($expected, $sut->getIdentifier());
    }

    #[Test]
    public function itAppendsIdentifiers(): void
    {
        $identifier = new Identifier('test');
        $expected = $identifier->append('appended');


        $sut = new class ($identifier) extends Validated {
            public function testAppendedIdentifier() {
                return $this->appendedIdentifier('appended');
            }
        };

        self::assertEquals($expected, $sut->testAppendedIdentifier());
    }

    #[Test]
    public function itDoesNotStartWithWarnings(): void
    {
        $sut = new class (new Identifier('test')) extends Validated {
        };

        self::assertFalse($sut->hasWarnings());
    }

    #[Test]
    public function itAddsWarnings(): void
    {
        $identifier = new Identifier('test');
        $message = 'duck!';
        $code = 'quack';
        $expected = new Warnings(
            $identifier,
            new Warning($message, $code),
        );

        $sut = new class ($identifier) extends Validated {
            public function testAddWarning (string $message, string $code) {
                $this->addWarning($message, $code);
            }
        };

        self::assertFalse($sut->hasWarnings());

        $sut->testAddWarning($message, $code);

        self::assertTrue($sut->hasWarnings());
        self::assertEquals($expected, $sut->getWarnings());
    }
}
