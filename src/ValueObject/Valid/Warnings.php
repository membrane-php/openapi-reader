<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

final class Warnings implements HasIdentifier
{
    /** @var Warning[] */
    private array $warnings;

    public function __construct(
        private readonly Identifier $identifier,
        Warning ...$warnings
    ) {
        $this->warnings = $warnings;
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    public function add(string $message, string $code): void
    {
        $this->warnings[] = new Warning($message, $code);
    }

    /** @return Warning[] */
    public function all(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function hasWarningCodes(string $code, string ...$codes): bool
    {
        $codes = [$code, ...$codes];

        foreach ($this->warnings as $warning) {
            if (in_array($warning->code, $codes)) {
                return true;
            }
        }

        return false;
    }
}
