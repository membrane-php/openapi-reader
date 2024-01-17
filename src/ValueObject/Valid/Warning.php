<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

final class Warning
{
    /**
     * Server Variable: "enum" SHOULD NOT be empty
     * Schema: "enum" SHOULD have at least one element
     */
    public const EMPTY_ENUM = 'empty-enum';

    /**
     * Path Item: "path" can be empty due to ACL constraints, but Membrane can't do much without any operations
     */
    public const EMPTY_PATH = 'empty-path';

    /**
     * OpenAPI: "paths" can be empty due to ACL constraints, but Membrane can't do much without any paths
     */
    public const EMPTY_PATHS = 'empty-paths';

    /**
     * Server Variable: If the "enum" is defined, the value SHOULD exist in the enum's values.
     */
    public const IMPOSSIBLE_DEFAULT = 'impossible-default';

    /**
     * OpenAPI, Path Item, Operation: If "servers" are specified, and they're all impossible. Your OpenAPI is unusable.
     */
    public const NO_VALID_SERVERS = 'no-valid-servers';

    /**
     * Path Item: "head"
     * Path Item: "options" specifies what HTTP methods are available, this is what your OpenAPI already does.
     * Path Item: "trace"
     */
    public const REDUNDANT_METHOD = 'redundant-method';

    /**
     * Path Item, Operation: "parameters" can have identical/similar names, but this could be quite confusing.
     */
    public const SIMILAR_NAMES = 'similar-names';

    public function __construct(
        public readonly string $message,
        public readonly string $code
    ) {
    }
}
