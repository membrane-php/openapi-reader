<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

final class Warning
{
    public const EMPTY_PATH = 'empty-path';
    public const EMPTY_PATHS = 'empty-paths';
    public const REDUNDANT_METHOD = 'redundant-method';
    public const SIMILAR_NAMES = 'similar-names';

    public function __construct(
        public readonly string $message,
        public readonly string $code
    ) {
    }
}
