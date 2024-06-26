<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

abstract class Validated implements HasIdentifier, HasWarnings
{
    private Warnings $warnings;

    public function __construct(
        private readonly Identifier $identifier,
    ) {
        $this->warnings = new Warnings($this->identifier);
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    protected function appendedIdentifier(
        string $primaryId,
        string $secondaryId = ''
    ): Identifier {
        return $this->identifier->append($primaryId, $secondaryId);
    }


    public function hasWarnings(): bool
    {
        return $this->warnings->hasWarnings();
    }

    public function getWarnings(): Warnings
    {
        return $this->warnings;
    }

    protected function addWarning(string $message, string $code): void
    {
        $this->getWarnings()->add($message, $code);
    }
}
