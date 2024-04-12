<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

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

    public function __construct(
        Identifier $parentIdentifier,
        Partial\Parameter $parameter
    ) {
        $this->name = $parameter->name ??
            throw InvalidOpenAPI::parameterMissingName($parentIdentifier);

        $this->in = $this->validateIn(
            $parentIdentifier,
            $parameter->in,
        );

        $identifier = $parentIdentifier->append($this->name, $this->in->value);
        parent::__construct($identifier);

        $this->required = $this->validateRequired(
            $identifier,
            $this->in,
            $parameter->required
        );

        $this->style = $this->validateStyle(
            $identifier,
            $this->in,
            $parameter->style,
        );

        $this->explode = $parameter->explode ?? $this->style->defaultExplode();

        if (isset($parameter->schema) !== empty($parameter->content)) {
            throw InvalidOpenAPI::mustHaveSchemaXorContent($parameter->name);
        }

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
                $parameter->name,
                $parameter->content
            );
        }

        $this->checkStyleSuitability();
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

    public function isIdentical(Parameter $other): bool
    {
        return $this->name === $other->name && $this->in === $other->in;
    }

    public function isSimilar(Parameter $other): bool
    {
        return $this->name !== $other->name &&
            mb_strtolower($this->name) === mb_strtolower($other->name);
    }

    public function canConflict(Parameter $other): bool
    {
        if (
            $this->in !== $other->in || // parameter can be identified by differing location
            $this->style !== $other->style || // parameter can be identified by differing style
            $this->in !== In::Query
        ) {
            return false;
        }

        return match ($this->style) {
            Style::Form =>
                $this->explode &&
                $other->explode &&
                $this->getSchema()->canBe(Type::Object),
            Style::PipeDelimited, Style::SpaceDelimited =>
                !$this->getSchema()->canOnlyBePrimitive(),
            default => false,
        };
    }

    private function validateIn(Identifier $identifier, ?string $in): In
    {
        if (is_null($in)) {
            throw InvalidOpenAPI::parameterMissingLocation($identifier);
        }

        return In::tryFrom($in) ??
            throw InvalidOpenAPI::parameterInvalidLocation($identifier);
    }

    private function validateRequired(
        Identifier $identifier,
        In $in,
        ?bool $required
    ): bool {
        if ($in === In::Path && $required !== true) {
            throw InvalidOpenAPI::parameterMissingRequired($identifier);
        }

        return $required ?? false;
    }

    private function validateStyle(
        Identifier $identifier,
        In $in,
        ?string $style
    ): Style {
        if (is_null($style)) {
            return Style::default($in);
        }

        $style = Style::tryFrom($style) ??
            throw InvalidOpenAPI::parameterInvalidStyle($identifier);

        $style->isAllowed($in) ?:
            throw InvalidOpenAPI::parameterIncompatibleStyle($identifier);

        return $style;
    }

    /**
     * @param array<Partial\MediaType> $content
     * @return array<string,MediaType>
     */
    private function validateContent(
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

    private function checkStyleSuitability(): void
    {
        foreach (Type::casesForVersion(OpenAPIVersion::Version_3_0) as $type) {
            if (
                $this->getSchema()->canBe($type) &&
                $this->style->isSuitableFor($type)
            ) {
                return;
            }
        }
        $this->addWarning(
            'unsuitable style for primitive data types',
            Warning::UNSUITABLE_STYLE
        );
    }

}
