<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class Schema extends Validated
{
    public readonly ?string $type;
    /** @var self[]|null  */
    public readonly ?array $allOf;
    /** @var self[]|null  */
    public readonly ?array $anyOf;
    /** @var self[]|null  */
    public readonly ?array $oneOf;

    public function __construct(
        Identifier $identifier,
        Partial\Schema $schema
    ) {
        parent::__construct($identifier);

        $this->type = $schema->type ?? null;

        if (isset($schema->allOf)) {
            if (empty($schema->allOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->allOf = $this->getSubSchemas('allOf', $schema->allOf);
        } else {
            $this->allOf = null;
        }

        if (isset($schema->anyOf)) {
            if (empty($schema->anyOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->anyOf = $this->getSubSchemas('anyOf', $schema->anyOf);
        } else {
            $this->anyOf = null;
        }

        if (isset($schema->oneOf)) {
            if (empty($schema->oneOf)) {
                throw InvalidOpenAPI::emptyComplexSchema($this->getIdentifier());
            }
            $this->oneOf = $this->getSubSchemas('oneOf', $schema->oneOf);
        } else {
            $this->oneOf = null;
        }
    }

    /**
     * @param Partial\Schema[] $subSchemas
     * @return self[]
     */
    private function getSubSchemas(string $keyword, array $subSchemas): array
    {
        $result = [];
        foreach ($subSchemas as $index => $subSchema) {
            $result[] = new Schema(
                $this->getIdentifier()->append("$keyword($index)"),
                $subSchema
            );
        }
        return $result;
    }

    public function canItBeAnObject(): bool
    {
        return $this->canItBeThisType('object');
    }

    public function canItBeAnArray(): bool
    {
        return $this->canItBeThisType('array');
    }

    private function canItBeThisType(string $type, string ...$types): bool
    {
        if (in_array($this->type, [$type, ...$types])) {
            return true;
        }

        return array_reduce(
            [...($this->allOf ?? []), ...($this->anyOf ?? []), ...($this->oneOf ?? [])],
            fn($v, Schema $schema) => $v || $schema->canItBeThisType($type, ...$types),
            false
        );
    }
}
