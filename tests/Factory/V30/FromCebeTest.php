<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Factory\V30;

use cebe\openapi\spec as Cebe;
use Generator;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\Factory\V30\FromCebe;
use Membrane\OpenAPIReader\Tests\Fixtures\Helper\OpenAPIProvider;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\MediaType;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Operation;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\PathItem;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\Server;
use Membrane\OpenAPIReader\ValueObject\Valid\V30\ServerVariable;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid\Warnings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FromCebe::class)]
#[UsesClass(InvalidOpenAPI::class)]
#[UsesClass(OpenAPI::class)]
#[UsesClass(Partial\OpenAPI::class)]
#[UsesClass(Server::class)]
#[UsesClass(Partial\Server::class)]
#[UsesClass(ServerVariable::class)]
#[UsesClass(Partial\ServerVariable::class)]
#[UsesClass(PathItem::class)]
#[UsesClass(Partial\PathItem::class)]
#[UsesClass(Operation::class)]
#[UsesClass(Partial\Operation::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Partial\Parameter::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Partial\Schema::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Partial\MediaType::class)]
#[UsesClass(Validated::class)]
#[UsesClass(Warning::class)]
#[UsesClass(Warnings::class)]
#[UsesClass(Identifier::class)]
#[UsesClass(Method::class)]
#[UsesClass(Type::class)]
#[UsesClass(Style::class)]
class FromCebeTest extends TestCase
{
    #[Test, DataProvider('provideValidSpecs')]
    public function itConstructsValidOpenAPI(
        OpenAPI $expected,
        Cebe\OpenApi $openApi,
    ): void {
        self::assertEquals($expected, FromCebe::createOpenAPI($openApi));
    }

    #[Test, DataProvider('provideInvalidSpecs')]
    public function itCannotConstructInvalidOpenAPI(
        InvalidOpenAPI $expected,
        Cebe\OpenApi $openApi,
    ): void {
        self::expectExceptionObject($expected);

        FromCebe::createOpenAPI($openApi);
    }

    public static function provideValidSpecs(): Generator
    {
        yield 'minimal OpenAPI' => [
            OpenAPIProvider::minimalV30MembraneObject(),
            OpenAPIProvider::minimalV30CebeObject(),
        ];

        yield 'detailed OpenAPI' => [
            OpenAPIProvider::detailedV30MembraneObject(),
            OpenAPIProvider::detailedV30CebeObject(),
        ];
    }

    public static function provideInvalidSpecs(): Generator
    {
        yield 'no title' => [
            InvalidOpenAPI::missingInfo(),
            new Cebe\OpenApi([
                'openapi' => '3.0.0',
                'info' => ['version' => '0.1']
            ])
        ];

        yield 'no version' => [
            InvalidOpenAPI::missingInfo(),
            new Cebe\OpenApi([
                'openapi' => '3.0.0',
                'info' => ['title' => 'Slapdash API']
            ])
        ];
    }
}
