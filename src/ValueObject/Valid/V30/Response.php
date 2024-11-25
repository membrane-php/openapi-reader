<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V30;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class Response extends Validated
{
    public readonly string $description;

    /** @var array<string,Header> */
    public readonly array $headers;

    /** @var array<string,MediaType> */
    public readonly array $content;

    public function __construct(
        Identifier $identifier,
        Partial\Response $response,
    ) {
        parent::__construct($identifier);

        $this->description = $response->description
            ?? throw InvalidOpenAPI::missingRequiredField($identifier, 'description');

        $this->headers = $this->validateHeaders($identifier, $response->headers);

        $this->content = $this->validateContent($identifier, $response->content);
    }

    /**
     * @param array<Partial\Header> $headers
     * @return array<string, Header>
     */
    private function validateHeaders(
        Identifier $identifier,
        array $headers
    ): array {
        $result = [];
        foreach ($headers as $headerName => $header) {
            if ($this->headerShouldBeIgnored($headerName)) {
                continue;
            }

            $result[$headerName] = new Header(
                $identifier->append('header', $headerName),
                $header,
            );
        }

        return $result;
    }

    private function headerShouldBeIgnored(string $headerName): bool
    {
        return mb_strtolower($headerName) === 'content-type';
    }

    /**
     * @param array<Partial\MediaType> $content
     * @return array<string, MediaType>
    */
    private function validateContent(
        Identifier $identifier,
        array $content
    ): array {
        $result = [];
        foreach ($content as $mediaType) {
            if (!isset($mediaType->contentType)) {
                throw InvalidOpenAPI::contentMissingMediaType($identifier);
            }

            $result[$mediaType->contentType] = new MediaType(
                $identifier->append($mediaType->contentType),
                $mediaType,
            );
        }

        return $result;
    }
}
