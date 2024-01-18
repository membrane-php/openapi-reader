<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V30;

use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Server;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
#[CoversClass(Partial\Server::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(ServerVariable::class)]
#[UsesClass(Partial\ServerVariable::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
class ServerTest extends TestCase
{
    #[Test, DataProvider('provideServersToValidate')]
    public function itValidatesServers(
        InvalidOpenAPI $expected,
        Identifier $parentIdentifier,
        Partial\Server $server,
    ): void {
        self::expectExceptionObject($expected);

        new Server($parentIdentifier, $server);
    }

    #[Test, DataProvider('provideUrlPatterns')]
    public function itGetsTheUrlPattern(
        string $expected,
        Partial\Server $server
    ): void {
        $sut = new Server(new Identifier('test'), $server);

        self::assertSame($expected, $sut->getPattern());
    }

    /**
     * @param string[] $expected
     */
    #[Test, DataProvider('provideUrlsToGetVariablesFrom')]
    #[TestDox('It returns a list of variable names in order of appearance within the URL')]
    public function itGetsVariableNames(
        array $expected,
        Partial\Server $server
    ): void {
        $sut = new Server(new Identifier('test'), $server);

        self::assertSame($expected, $sut->getVariableNames());
    }


    /**
     * @param Warning[] $expected
     */
    #[Test, DataProvider('provideServersWithWarnings')]
    #[TestDox('It warns against having a variable defined that does not appear in the "url')]
    public function itWarnsAgainstRedundantVariables(
        array $expected,
        Partial\Server $server
    ): void {
        $sut = new Server(new Identifier('test'), $server);

        self::assertEquals($expected, $sut->getWarnings()->all());
    }



    public static function provideServersToValidate(): Generator
    {
        $parentIdentifier = new Identifier('test');

        $case = fn($expected, $data) => [
            $expected,
            $parentIdentifier,
            PartialHelper::createServer(...$data)
        ];

        yield 'missing "url"' => $case(
            InvalidOpenAPI::serverMissingUrl($parentIdentifier),
            ['url' => null]
        );

        yield '"url" with one undefined variable' => $case(
            InvalidOpenAPI::serverHasUndefinedVariables(
                $parentIdentifier->append('https://server.net/{var1}'),
                'var1'
            ),
            ['url' => 'https://server.net/{var1}', 'variables' => []]
        );

        yield '"url" with three undefined variable' => $case(
            InvalidOpenAPI::serverHasUndefinedVariables(
                $parentIdentifier->append('https://server.net/{var1}/{var2}/{var3}'),
                'var1',
                'var2',
                'var3',
            ),
            [
                'url' => 'https://server.net/{var1}/{var2}/{var3}',
                'variables' => [],
            ]
        );

        yield '"url" with one defined and one undefined variable' => $case(
            InvalidOpenAPI::serverHasUndefinedVariables(
                $parentIdentifier->append('https://server.net/{var1}/{var2}'),
                'var2',
            ),
            [
                'url' => 'https://server.net/{var1}/{var2}',
                'variables' => [
                    PartialHelper::createServerVariable(name: 'var1')
                ],
            ]
        );
    }

    private static function createServerWithVariables(string ...$variables): Partial\Server
    {
        return PartialHelper::createServer(
            url: sprintf(
                'https://server.net/%s',
                implode('/', array_map(fn($v) => sprintf('{%s}', $v), $variables))
            ),
            variables: array_map(
                fn($v) => PartialHelper::createServerVariable(name: $v),
                $variables
            )
        );
    }

    /**
     * @return Generator<array{0:string, 1:Partial\Server}>
     */
    public static function provideUrlPatterns(): Generator
    {
        yield 'url without variables' => [
            'https://server.net/',
            self::createServerWithVariables(),
        ];

        yield 'url with one variable' => [
            'https://server.net/([^/]+)',
            self::createServerWithVariables('v1'),
        ];

        yield 'url with three variables' => [
            'https://server.net/([^/]+)/([^/]+)/([^/]+)',
            self::createServerWithVariables('v1', 'v2', 'v3'),
        ];
    }

    /**
     * @return Generator<array{0:string[], 1:Partial\Server}>
     */
    public static function provideUrlsToGetVariablesFrom(): Generator
    {
        $case = fn(array $variables) => [
            $variables,
            self::createServerWithVariables(...$variables),
        ];

        yield 'no variables' => $case([]);
        yield 'one variable' => $case(['var1']);
        yield 'three variables' => $case(['var1', 'var2', 'var3']);
    }

    /**
     * @return Generator<array{0:Warning[], 1:Partial\Server}>
     */
    public static function provideServersWithWarnings(): Generator
    {
        $redundantVariables = fn($variables) => [
           array_map(
               fn($v) => new Warning(
                   sprintf('"variables" defines "%s" which is not found in "url".', $v),
                   Warning::REDUNDANT_VARIABLE
               ),
               $variables
           ),
            PartialHelper::createServer(
                variables: array_map(
                    fn($v) => PartialHelper::createServerVariable(name: $v),
                    $variables,
                )
            )
        ];

        yield 'no warnings' => $redundantVariables([]);
        yield 'one redundant variable' => $redundantVariables(['v1']);
        yield 'three redundant variables' => $redundantVariables(['v1', 'v2', 'v3']);
    }
}
