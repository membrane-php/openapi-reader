<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Identifier::class)]
class IdentifierTest extends TestCase
{
    public static function provideArraysOfStringsToConstructFrom(): Generator
    {
        yield 'single string' => ['api'];
        yield 'two strings' => ['api', '/path'];
        yield 'three strings' => ['api', '/path', 'get'];


    }

    #[Test, DataProvider('provideArraysOfStringsToConstructFrom')]
    #[TestDox('it returns a new instance of itself with a single string appended to the end of the chain')]
    public function itCanBeAppended(string ...$chain): void
    {
        $stringToAppend = 'appended';
        $expected = new Identifier(...[...$chain, $stringToAppend]);

        $sut = new Identifier(...$chain);

        $actual = $sut->append($stringToAppend);

        self::assertEquals($expected, $actual);
    }

    public static function provideChainsToCastToString(): Generator
    {
        yield 'single field' => [
            '["field1"]',
            ['field1'],
        ];

        yield 'three fields' => [
            '["field1"]["field2"]["field3"]',
            ['field1', 'field2', 'field3']
        ];
    }

    /** @param string[] $chain */
    #[Test, DataProvider('provideChainsToCastToString')]
    public function itCanBeCastToString(string $expected, array $chain): void
    {
        $sut = new Identifier(...$chain);

        self::assertSame($expected, (string)$sut);
    }

    public static function provideChainsToSearchThrough(): Generator
    {
        yield 'single field, first field from end' => [
            'field1',
            0,
            ['field1']
        ];

        yield 'single field, second field from end which should be null' => [
            null,
            1,
            ['field1']
        ];

        yield 'three fields, pick the first from end' => [
            'field3',
            0,
            ['field1', 'field2', 'field3']
        ];

        yield 'three fields, pick the second from end' => [
            'field2',
            1,
            ['field1', 'field2', 'field3']
        ];

        yield 'three fields, pick the third from end' => [
            'field1',
            2,
            ['field1', 'field2', 'field3']
        ];
    }

    /** @param string[] $chain */
    #[Test, DataProvider('provideChainsToSearchThrough')]
    public function itGetsStringsFromEndOfChain(
        ?string $expected,
        int $index,
        array $chain,
    ): void {
        $sut = new Identifier(...$chain);

        self::assertSame($expected, $sut->fromEnd($index));
    }
}
