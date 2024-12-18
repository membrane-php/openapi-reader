<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

/**
 * A Validated object **may** make _opinionated simplifications_ to improve DX.
 * - It **may** change _appearance_ from its OpenAPI counterpart.
 * - It **must** express the same _intent_ as its OpenAPI counterpart.
 */
abstract class Validated implements HasIdentifier, HasWarnings
{
    private Warnings $warnings;

    public function __construct(
        private readonly Identifier $identifier,
        Warnings|null $warnings = null,
    ) {
        $this->warnings = $warnings ?? new Warnings($this->identifier);
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

    /** @return Warnings contains the list of issues found during validation */
    public function getWarnings(): Warnings
    {
        return $this->warnings;
    }

    protected function addWarning(string $message, string $code): void
    {
        $this->getWarnings()->add($message, $code);
    }
}
