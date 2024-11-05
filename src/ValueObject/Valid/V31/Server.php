<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;
use Membrane\OpenAPIReader\ValueObject\Valid\Warning;

final class Server extends Validated
{
    /**
     * REQUIRED
     */
    public readonly string $url;

    /**
     * When the url has a variable named in {brackets}
     * This array MUST contain the definition of the corresponding variable.
     *
     * The name of the variable is mapped to the Server Variable Object
     * @var array<string,ServerVariable>
     */
    public readonly array $variables;

    public function __construct(
        Identifier $parentIdentifier,
        Partial\Server $server,
    ) {
        if (!isset($server->url)) {
            throw InvalidOpenAPI::serverMissingUrl($parentIdentifier);
        }

        parent::__construct($parentIdentifier->append($server->url));

        $this->url = $this->validateUrl($parentIdentifier, $server->url);

        $this->variables = $this->validateVariables(
            $this->getIdentifier(),
            $this->getVariableNames(),
            $server->variables,
        );
    }

    /**
     * Returns the list of variable names in order of appearance within the URL.
     * @return array<int, string>
     */
    public function getVariableNames(): array
    {
        preg_match_all('#{[^/]+}#', $this->url, $result);

        return array_map(fn($v) => trim($v, '{}'), $result[0]);
    }

    /**
     * Returns the regex of the URL
     */
    public function getPattern(): string
    {
        $regex = preg_replace('#{[^/]+}#', '([^/]+)', $this->url);
        assert(is_string($regex));
        return $regex;
    }

    private function validateUrl(Identifier $identifier, string $url): string
    {
        $characters = str_split($url);

        $insideVariable = false;
        foreach ($characters as $character) {
            if ($character === '{') {
                if ($insideVariable) {
                    throw InvalidOpenAPI::urlNestedVariable(
                        $identifier->append($url)
                    );
                }
                $insideVariable = true;
            } elseif ($character === '}') {
                if (!$insideVariable) {
                    throw InvalidOpenAPI::urlLiteralClosingBrace(
                        $identifier->append($url)
                    );
                }
                $insideVariable = false;
            }
        }

        if ($insideVariable) {
            throw InvalidOpenAPI::urlUnclosedVariable($identifier->append($url));
        }

        if (str_ends_with($url, '/') && $url !== '/') {
            $this->addWarning(
                'paths begin with a forward slash, so servers need not end in one',
                Warning::REDUNDANT_FORWARD_SLASH,
            );
        }

        return rtrim($url, '/');
    }

    /**
     * @param array<int,string> $UrlVariableNames
     * @param Partial\ServerVariable[] $variables
     * @return array<string,ServerVariable>
     */
    private function validateVariables(
        Identifier $identifier,
        array $UrlVariableNames,
        array $variables,
    ): array {
        $result = [];
        foreach ($variables as $variable) {
            if (!isset($variable->name)) {
                throw InvalidOpenAPI::serverVariableMissingName($identifier);
            }

            if (!in_array($variable->name, $UrlVariableNames)) {
                $this->addWarning(
                    sprintf(
                        '"variables" defines "%s" which is not found in "url".',
                        $variable->name
                    ),
                    Warning::REDUNDANT_VARIABLE
                );

                continue;
            }

            $result[$variable->name] = new ServerVariable(
                $identifier->append($variable->name),
                $variable
            );
        }

        $undefined = array_diff($UrlVariableNames, array_keys($result));
        if (!empty($undefined)) {
            throw InvalidOpenAPI::serverHasUndefinedVariables(
                $identifier,
                ...$undefined,
            );
        }

        return $result;
    }
}
