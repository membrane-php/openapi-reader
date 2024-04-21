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

        isset($parameter->schema) === empty($parameter->content) ?:
            throw InvalidOpenAPI::mustHaveSchemaXorContent($parameter->name);

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

        $this->style = $this->validateStyle(
            $identifier,
            $this->getSchema(),
            $this->in,
            $parameter->style,
        );

        $this->explode = $parameter->explode ?? $this->style->defaultExplode();
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

    public function canConflictWith(Parameter $other): bool
    {
        return ($this->canCauseConflict() && $other->isVulnerableToConflict()) ||
            ($this->isVulnerableToConflict() && $other->canCauseConflict());
    }

    private function canCauseConflict(): bool
    {
        return $this->in === In::Query &&
            $this->style === Style::Form &&
            $this->explode &&
            $this->getSchema()->canBe(Type::Object);
    }

    private function isVulnerableToConflict(): bool
    {
        /**
         * @todo once schemas account for minItems and minProperties keywords.
         * pipeDelimited and spaceDelimited are also vulnerable if:
         * type:array and minItems <= 1
         * this is because there would be no delimiter to distinguish it from a form parameter
         *
         * form would not be vulnerable if:
         * explode:false
         * and...
         * type:object and minProperties > 1
         * or ...
         * type:array and minItems > 1
         * this is because there would be a delimiter to distinguish it from an exploding parameter
         */

        return $this->in === In::Query && match ($this->style) {
            Style::Form => true,
            Style::PipeDelimited,
            Style::SpaceDelimited => $this->getSchema()->canBePrimitive(),
            default => false
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
        Schema $schema,
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

        $style !== Style::DeepObject || $schema->canOnlyBe(Type::Object) ?:
            throw InvalidOpenAPI::deepObjectMustBeObject($identifier);

        if (
            in_array($style, [Style::SpaceDelimited, Style::PipeDelimited]) &&
            $schema->canBePrimitive()
        ) {
            $this->addWarning(
                "style:$style->value, is not allowed to be primitive." .
                'In these instances style:form is recommended.',
                Warning::UNSUITABLE_STYLE
            );
        }

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
}
