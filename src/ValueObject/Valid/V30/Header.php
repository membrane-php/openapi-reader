<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;
use Membrane\OpenAPIReader\ValueObject\Valid;

final class Header extends Validated implements Valid\Parameter
{
    public readonly Style $style;
    public readonly bool $explode;
    public readonly bool $required;

    /**
     * A Parameter MUST define a "schema" or "content" but not both
     */
    public readonly ?Schema $schema;

    /**
     * A Parameter MUST have a "schema" or "content" but not both
     * If "content" is defined:
     * - It MUST contain only one Media Type.
     * - That MediaType MUST define a schema.
     * @var array<string,MediaType>
     */
    public readonly array $content;

    public function __construct(
        Identifier $identifier,
        Partial\Parameter $parameter
    ) {
        parent::__construct($identifier);

        $this->style = $this->validateStyle($identifier, $parameter->style);
        $this->required = $parameter->required ?? false;
        $this->explode = $parameter->explode ?? Style::Simple->defaultExplode();


        isset($parameter->schema) === empty($parameter->content) ?:
            throw InvalidOpenAPI::mustHaveSchemaXorContent($identifier);

        if (isset($parameter->schema)) {
            $this->content = [];
            $this->schema = new Schema(
                $this->appendedIdentifier('schema'),
                $parameter->schema
            );
        } else {
            $this->schema = null;

            $this->content = $this->validateContent(
                $this->getIdentifier(),
                $parameter->content
            );
        }
    }

    public function getSchema(): Schema
    {
        if (isset($this->schema)) {
            return $this->schema;
        } else {
            assert(array_values($this->content)[0]->schema !== null);
            return array_values($this->content)[0]->schema;
        }
    }

    public function hasMediaType(): bool
    {
        return !empty($this->content);
    }

    public function getMediaType(): ?string
    {
        return array_key_first($this->content);
    }

    private function validateStyle(Identifier $identifier, ?string $style): Style
    {
        $defaultStyle = Style::default(In::Header);

        if ($style !== null &&  Style::from($style) !== $defaultStyle) {
            throw InvalidOpenAPI::parameterIncompatibleStyle($identifier);
        }

        return $defaultStyle;
    }

    /**
     * @param array<Partial\MediaType> $content
     * @return array<string,MediaType>
     */
    private function validateContent(Identifier $identifier, array $content): array
    {
        if (count($content) !== 1) {
            throw InvalidOpenAPI::parameterContentCanOnlyHaveOneEntry($identifier);
        }

        if (!isset($content[0]->contentType)) {
            throw InvalidOpenAPI::contentMissingMediaType($identifier);
        }

        if (!isset($content[0]->schema)) {
            throw InvalidOpenAPI::mustHaveSchemaXorContent($identifier);
        }

        return [
            $content[0]->contentType => new MediaType(
                $this->appendedIdentifier($content[0]->contentType),
                $content[0]
            ),
        ];
    }
}
