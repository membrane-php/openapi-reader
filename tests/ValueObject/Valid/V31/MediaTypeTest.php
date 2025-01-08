<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\PartialHelper;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\MediaType;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaType::class)]
#[CoversClass(Partial\MediaType::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Type::class)]
#[UsesClass(Warnings::class)]
class MediaTypeTest extends TestCase
{
    #[Test]
    public function itMayNotHaveASchema(): void
    {
        $sut = new MediaType(
            new Identifier('test-mediaType'),
            PartialHelper::createMediaType(schema: null)
        );

        self::assertNull($sut->schema);
    }

    #[Test]
    public function itMayHaveASchema(): void
    {
        $sut = new MediaType(
            new Identifier('test-mediaType'),
            PartialHelper::createMediaType(schema: PartialHelper::createSchema())
        );

        self::assertInstanceOf(Schema::class, $sut->schema);
    }
}
