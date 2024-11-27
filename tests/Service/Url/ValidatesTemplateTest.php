<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Service\Url;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Service\Url\ValidatesTemplate;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ValidatesTemplate::class)]
class ValidatesTemplateTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    #[DataProvider('provideValidUrls')]
    public function itValidatesTemplating(string $url): void
    {
        $sut = new ValidatesTemplate();

        self::expectNotToPerformAssertions();

        $sut(new Identifier('test'), $url);
    }

    #[Test]
    #[DataProvider('provideImproperlyTemplatedUrls')]
    public function itInvalidatesImproperTemplating(
        InvalidOpenAPI $expected,
        Identifier $identifier,
        string $url,
    ): void {
        $sut = new ValidatesTemplate();

        self::expectExceptionObject($expected);

        $sut($identifier, $url);
    }

    /** @return \Generator<array{0:string}> */
    public static function provideValidUrls(): \Generator
    {
        $partialUrls = ['', 'concrete', '{templated}', '{templated}/{twice}'];

        foreach ($partialUrls as $partialUrl) {
            foreach (self::provideVariedPrefixesToUrl($partialUrl) as $url) {
                yield $url => [$url];
            }
        }
    }

    /**
     * @return \Generator<array{
     *     0: InvalidOpenAPI,
     *     1: Identifier,
     *     2: string,
     * }>
     */
    public static function provideImproperlyTemplatedUrls(): \Generator
    {
        $identifier = new Identifier('test');

        foreach (['var}', '}var{'] as $partialUrl) {
            foreach (self::provideVariedPrefixesToUrl($partialUrl) as $url) {
                yield $url => [
                    InvalidOpenAPI::urlLiteralClosingBrace($identifier),
                    $identifier,
                    $url,
                ];
            }
        }

        foreach (['{var', '{var1}/{var2'] as $partialUrl) {
            foreach (self::provideVariedPrefixesToUrl($partialUrl) as $url) {
                yield $url => [
                    InvalidOpenAPI::urlUnclosedVariable($identifier),
                    $identifier,
                    $url,
                ];
            }
        }

        foreach (['{{var}}', '{var1{var2}}'] as $partialUrl) {
            foreach (self::provideVariedPrefixesToUrl($partialUrl) as $url) {
                yield $url => [
                    InvalidOpenAPI::urlNestedVariable($identifier),
                    $identifier,
                    $url,
                ];
            }
        }
    }

    /** @return list<string> */
    private static function provideVariedPrefixesToUrl(string $url): array
    {
        return [
            "https://server.net/$url",
            "/$url",
        ];
    }
}
