<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V31;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerVariable::class)]
#[CoversClass(Partial\ServerVariable::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
class ServerVariableTest extends TestCase
{
    #[Test, DataProvider('provideServerVariablesToInvalidate')]
    public function itValidatesServerVariables(
        InvalidOpenAPI $expected,
        Identifier $identifier,
        Partial\ServerVariable $partialServerVariable
    ): void {
        self::expectExceptionObject($expected);

        new ServerVariable($identifier, $partialServerVariable);
    }

    #[Test, DataProvider('provideServerVariablesToWarnAgainst')]
    public function itWarnsAgainstBadPractice(
        Warning $expected,
        Partial\ServerVariable $partialServerVariable,
    ): void {
        $sut = new ServerVariable(new Identifier('test'), $partialServerVariable);

        self::assertTrue($sut->hasWarnings());
        self::assertEquals($expected, $sut->getWarnings()->all()[0]);
    }

    public static function provideServerVariablesToInvalidate(): Generator
    {
        $identifier = new Identifier('test');

        $case = fn($expected, $data) => [
            $expected,
            $identifier,
            PartialHelper::createServerVariable(...$data)
        ];

        yield 'missing "default"' => $case(
            InvalidOpenAPI::serverVariableMissingDefault($identifier),
            ['default' => null],
        );
    }

    public static function provideServerVariablesToWarnAgainst(): Generator
    {
        $case = fn($message, $code, $data) => [
            new Warning($message, $code),
            PartialHelper::createServerVariable(...$data)
        ];

        yield 'missing "default" from "enum"' => $case(
            'If "enum" is defined, the "default" SHOULD exist within it.',
            Warning::IMPOSSIBLE_DEFAULT,
            ['default' => 'default-test-value', 'enum' => ['something-else']],
        );

        yield '"enum" is empty' => $case(
            'If "enum" is defined, it SHOULD NOT be empty',
            Warning::EMPTY_ENUM,
            ['enum' => []],
        );
    }
}
