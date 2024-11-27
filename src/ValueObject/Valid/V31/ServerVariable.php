<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class ServerVariable extends Validated
{
    /** REQUIRED */
    public readonly string $default;

    /**
     * If not null:
     * - It SHOULD NOT be empty
     * - It SHOULD contain the "default" value
     * @var list<string>|null
     */
    public readonly array|null $enum;

    public function __construct(
        Identifier $identifier,
        Partial\ServerVariable $serverVariable,
    ) {
        parent::__construct($identifier);

        if (!isset($serverVariable->default)) {
            throw InvalidOpenAPI::serverVariableMissingDefault($identifier);
        }

        $this->default = $serverVariable->default;

        if (isset($serverVariable->enum)) {
            if (empty($serverVariable->enum)) {
                $this->addWarning(
                    'If "enum" is defined, it SHOULD NOT be empty',
                    Warning::EMPTY_ENUM,
                );
            }

            if (!in_array($serverVariable->default, $serverVariable->enum)) {
                $this->addWarning(
                    'If "enum" is defined, the "default" SHOULD exist within it.',
                    Warning::IMPOSSIBLE_DEFAULT
                );
            }
        }

        $this->enum = $serverVariable->enum;
    }
}
