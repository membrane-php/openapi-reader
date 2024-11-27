<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

interface Server
{
    /**
     * @return string
     *     The regex matching the URL.
     */
    public function getPattern(): string;

    /**
     * @return list<string>
     *     ordered list of variable names
     *     matching their order of appearance within the URL.
     */
    public function getVariableNames(): array;

    /**
     * @phpstan-assert-if-true false $this->isTemplated()
     * @return bool
     *     true if the URL does not contain variables,
     *     false otherwise.
     */
    public function isConcrete(): bool;

    /**
     * @phpstan-assert-if-true false $this->isConcrete()
     * @phpstan-assert-if-true =non-empty-list<string> $this->getVariableNames()
     * @return bool
     *     true if the URL does contain variables,
     *     false otherwise.
     */
    public function isTemplated(): bool;
}
