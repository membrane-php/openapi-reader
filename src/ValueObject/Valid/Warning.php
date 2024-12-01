<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid;

final class Warning
{
    public function __construct(
        public readonly string $message,
        public readonly string $code
    ) {
    }

    /**
     * Warning code given for:
     *     An API that is technically invalid but safely corrected.
     */
    public const INVALID_API = 'invalid-api';

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
     * OpenAPI, Path, Operation: Servers with identical urls only serve to confuse
     */
    public const IDENTICAL_SERVER_URLS = 'identical-server-urls';

    /**
     * Server Variable: If the "enum" is defined, the value SHOULD exist in the enum's values.
     */
    public const IMPOSSIBLE_DEFAULT = 'impossible-default';

    /**
     * Schema: No value, of any data type, can satisfy this schema
     */
    public const IMPOSSIBLE_SCHEMA = 'impossible-schema';

    /**
     * Server: paths begin with a forward slash, so servers need not end in one
     * - membrane will ignore trailing forward slashes on server urls
     */
    public const REDUNDANT_FORWARD_SLASH = 'redundant-forward-slash';

    /**
     * Path Item:
     * - "head", "options" and "trace" are not particularly valuable in an OpenAPI.
     * - For example: "options" specifies what HTTP methods are available, this is what your OpenAPI already does.
     */
    public const REDUNDANT_METHOD = 'redundant-method';

    /**
     * Server: If the "url" does not name the variable, it cannot be provided.
     */
    public const REDUNDANT_VARIABLE = 'redundant-variable';

    /**
     * Path Item, Operation: "parameters" can have identical/similar names, but this could be quite confusing.
     */
    public const SIMILAR_NAMES = 'similar-names';

    /**
     * Parameter:
     * - spaceDelimited and pipeDelimited styles should not be used for primitive values
     * - deepObject style should only be used for object values
     */
    public const UNSUITABLE_STYLE = 'unsuitable-style';
}
