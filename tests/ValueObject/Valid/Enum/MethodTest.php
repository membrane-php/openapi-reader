<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\Enum;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Method::class)]
class MethodTest extends TestCase
{

    #[Test, DataProvider('provideRedundantMethods')]
    public function itKnowsIfItIsRedundant(Method $sut): void
    {
        self::assertTrue($sut->isRedundant());
    }

    public static function provideRedundantMethods(): Generator
    {
        yield 'head' => [Method::HEAD];
        yield 'options' => [Method::OPTIONS];
        yield 'trace' => [Method::TRACE];
    }
}
