<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Exception;

use RuntimeException;

/*
 * This exception occurs when an error occurs reading all, or part, of the file provided.
 * This may be due to one of the following reasons:
 * 1: The file, or its references, cannot be found
 * 2: The data is invalid according to the associated format's specification
 */
final class CannotRead extends RuntimeException
{
    public const FILE_NOT_FOUND = 0;
    public const UNMATCHED_FILE_TYPE = 1;
    public const INVALID_FORMATTING = 2;
    public const UNRESOLVEABLE_REFERENCE = 3;

    public static function fileNotFound(string $path): self
    {
        $message = sprintf('%s not found at %s', pathinfo($path, PATHINFO_BASENAME), $path);
        return new self($message, self::FILE_NOT_FOUND);
    }

    public static function unrecognizedFileFormat(string $filePath): self
    {
        $message = sprintf('"%s" could not be matched to a supported file format', $filePath);
        return new self($message, self::UNMATCHED_FILE_TYPE);
    }

    public static function invalidFormatting(\Throwable $e): self
    {
        $message = 'Data is not formatted correctly';
        return new self($message, self::INVALID_FORMATTING, $e);
    }

    public static function cannotResolveExternalReferencesFromString(): self
    {
        $message = 'External References cannot be resolved when reading from string';
        return new self($message, self::UNRESOLVEABLE_REFERENCE);
    }

    public static function unresolvedReference(\Throwable $e): self
    {
        $message = 'References could not be resolved';
        return new self($message, self::UNRESOLVEABLE_REFERENCE, $e);
    }
}
