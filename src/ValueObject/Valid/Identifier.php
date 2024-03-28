<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

use Stringable;

final class Identifier implements Stringable
{
    /** @var string[] */
    private readonly array $chain;

    public function __construct(string $field, string ...$fields)
    {
        $this->chain = [$field, ...$fields];
    }

    public function append(string $primaryId, string $secondaryId = ''): self
    {
        $field = sprintf(
            '%s%s',
            $primaryId,
            $secondaryId === '' ? '' : "($secondaryId)"
        );

        return new self(...[...$this->chain, $field]);
    }

    public function fromEnd(int $level): ?string
    {
        return array_reverse($this->chain)[$level] ?? null;
    }

    public function __toString()
    {
        return sprintf('["%s"]', implode('"]["', $this->chain));
    }
}
