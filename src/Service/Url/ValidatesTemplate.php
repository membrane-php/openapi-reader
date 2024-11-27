<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Service\Url;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;

/** @internal */
final class ValidatesTemplate
{
    public function __invoke(Identifier $identifier, string $url): void
    {
        $characters = str_split($url);

        $insideVariable = false;
        foreach ($characters as $character) {
            if ($character === '{') {
                if ($insideVariable) {
                    throw InvalidOpenAPI::urlNestedVariable($identifier);
                }
                $insideVariable = true;
            } elseif ($character === '}') {
                if (!$insideVariable) {
                    throw InvalidOpenAPI::urlLiteralClosingBrace($identifier);
                }
                $insideVariable = false;
            }
        }

        if ($insideVariable) {
            throw InvalidOpenAPI::urlUnclosedVariable($identifier);
        }
    }
}
