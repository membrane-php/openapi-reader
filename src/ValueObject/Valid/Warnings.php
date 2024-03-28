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

    /** @return Warning[] */
    public function findByWarningCode(string $code, string ...$codes): array
    {
        return array_filter(
            $this->warnings,
            fn($w) => in_array($w->code, [$code, ...$codes])
        );
    }

    public function hasWarningCode(string $code, string ...$codes): bool
    {
        return !empty($this->findByWarningCode($code, ...$codes));
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}
