<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\In;
use Membrane\OpenAPIReader\Style;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class Parameter extends Validated
{
    /** REQUIRED */
    public readonly string $name;

    /** REQUIRED */
    public readonly In $in;

    /**
     * If "in":"path"
     * - "required" MUST be defined
     * - "required" MUST be set to true
     */
    public readonly bool $required;

    public readonly Style $style;
    public readonly bool $explode;

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

    public function __construct(Identifier $parentIdentifier, Partial\Parameter $parameter)
    {
        if (!isset($parameter->name)) {
            throw InvalidOpenAPI::parameterMissingName($parentIdentifier);
        }
        $this->name = $parameter->name;

        if (!isset($parameter->in)) {
            throw InvalidOpenAPI::parameterMissingLocation($parentIdentifier);
        }

        parent::__construct($parentIdentifier->append($parameter->name, $parameter->in));

        $this->in = In::tryFrom($parameter->in) ??
            throw InvalidOpenAPI::parameterInvalidLocation($this->getIdentifier());

        if ($this->in === In::Path && $parameter->required !== true) {
            throw InvalidOpenAPI::parameterMissingRequired($this->getIdentifier());
        }
        
        $this->required = $parameter->required ?? false;

        if (!isset($parameter->style)) {
            $this->style = $this->defaultStyle($this->in);
        } elseif (Style::tryFrom($parameter->style) === null) {
            throw InvalidOpenAPI::parameterInvalidStyle($this->getIdentifier());
        } else {
            $this->style = Style::from($parameter->style);
        }

        if (!$this->styleIsValid($this->in, $this->style)) {
            throw InvalidOpenAPI::parameterIncompatibleStyle($this->getIdentifier());
        }

        $this->explode = $parameter->explode ?? $this->defaultExplode($this->style);

        if (isset($parameter->schema) !== empty($parameter->content)) {
            throw InvalidOpenAPI::mustHaveSchemaXorContent($parameter->name);
        }

        if (isset($parameter->schema)) {
            $this->schema = new Schema(
                $this->appendedIdentifier('schema'),
                $parameter->schema
            );
            $this->content = [];
        } else {
            $this->schema = null;

            $this->content = $this->getContent(
                $this->getIdentifier(),
                $parameter->name,
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

    private function defaultStyle(In $in): Style
    {
        return match ($in) {
            In::Path, In::Header => Style::Simple,
            In::Query, In::Cookie => Style::Form,
        };
    }

    private function defaultExplode(Style $style): bool
    {
        return $style === Style::Form;
    }

    private function styleIsValid(In $in, Style $style): bool
    {
        return in_array(
            $style,
            match ($in) {
                In::Path => [Style::Matrix, Style::Label, Style::Simple],
                In::Query => [Style::Form, Style::SpaceDelimited, Style::PipeDelimited, Style::DeepObject],
                In::Header => [Style::Simple],
                In::Cookie => [Style::Form],
            },
            true
        );
    }

    /**
     * @param array<Partial\MediaType> $content
     * @return array<string,MediaType>
     */
    private function getContent(
        Identifier $identifier,
        string $name,
        array $content,
    ): array {
        if (count($content) !== 1) {
            throw InvalidOpenAPI::parameterContentCanOnlyHaveOneEntry($this->getIdentifier());
        }

        if (!isset($content[0]->contentType)) {
            throw InvalidOpenAPI::contentMissingMediaType($identifier);
        }

        if (!isset($content[0]->schema)) {
            throw InvalidOpenAPI::mustHaveSchemaXorContent($name);
        }

        return [
            $content[0]->contentType => new MediaType(
                $this->appendedIdentifier($content[0]->contentType),
                $content[0]
            ),
        ];
    }
}
