<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\V31;

use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\Validated;

final class RequestBody extends Validated
{
    public readonly string|null $description;

    public readonly bool $required;

    /**
     * REQUIRED
     * @var array<string,MediaType>
     */
    public readonly array $content;

    public function __construct(
        Identifier $parentIdentifier,
        Partial\RequestBody $requestBody,
    ) {
        $identifier = $parentIdentifier->append('requestBody');
        parent::__construct($identifier);

        $this->description = $requestBody->description;
        $this->required = $requestBody->required;

        $this->content = $this->validateContent(
            $identifier,
            $requestBody->content
        );
    }

    /**
     * @param array<Partial\MediaType> $content
     * @return array<string, MediaType>
    */
    public function validateContent(
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
