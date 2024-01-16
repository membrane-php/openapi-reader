<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

final class Warnings
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
}
