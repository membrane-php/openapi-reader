<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Tests\Fixtures\ProvidesInvalidatedSchemas;
use Membrane\OpenAPIReader\Tests\Fixtures\ProvidesReviewedSchemas;
use Membrane\OpenAPIReader\Tests\Fixtures\ProvidesSimplifiedSchemas;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\Keywords;
use Membrane\OpenAPIReader\ValueObject\Valid\V31\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema::class)]
#[CoversClass(Partial\Schema::class)] // DTO
#[CoversClass(InvalidOpenAPI::class)]
#[UsesClass(Type::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
class KeywordsTest extends TestCase
{
    #[Test]
    #[TestDox('It reviews schema keywords for recoverable issues')]
    #[DataProviderExternal(ProvidesReviewedSchemas::class, 'forV3X')]
    public function itReviewsKeywords(Partial\Schema $schema, Warning $warning): void
    {
        $identifier = new Identifier('sut');
        $sut = new Keywords($identifier, new Warnings($identifier), $schema);

        self::assertContainsEquals($warning, $sut->getWarnings()->all());
    }

    #[Test]
    #[TestDox('It simplifies schema keywords where possible')]
    #[DataProviderExternal(ProvidesSimplifiedSchemas::class, 'forV3X')]
    #[DataProviderExternal(ProvidesSimplifiedSchemas::class, 'forV31')]
    public function itSimplifiesKeywords(
        Partial\Schema $schema,
        string $propertyName,
        mixed $expected,
    ): void {
        $identifier = new Identifier('sut');
        $sut = new Keywords($identifier, new Warnings($identifier), $schema);

        self::assertEquals($expected, $sut->{$propertyName});
    }

    #[Test]
    #[TestDox('It invalidates schema keywords for non-recoverable issues')]
    #[DataProviderExternal(ProvidesInvalidatedSchemas::class, 'forV3X')]
    #[DataProviderExternal(ProvidesInvalidatedSchemas::class, 'forV31')]
    public function itInvalidatesKeywords(
        InvalidOpenAPI $expected,
        Identifier $identifier,
        Partial\Schema $schema,
    ): void {
        $warnings = new Warnings($identifier);

        self::expectExceptionObject($expected);

        new Keywords($identifier, $warnings, $schema);
    }
}
