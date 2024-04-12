<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Enum;

enum Style: string
{
    case Matrix = 'matrix';
    case Label = 'label';
    case Form = 'form';
    case Simple = 'simple';
    case SpaceDelimited = 'spaceDelimited';
    case PipeDelimited = 'pipeDelimited';
    case DeepObject = 'deepObject';

    public static function default(In $in): self
    {
        return match ($in) {
            In::Path, In::Header => self::Simple,
            In::Query, In::Cookie => self::Form,
        };
    }

    public function defaultExplode(): bool
    {
        return $this === self::Form;
    }

    public function isAllowed(In $in): bool
    {
        return in_array($this, (match ($in) {
            In::Path => [
                self::Matrix,
                self::Label,
                self::Simple
            ],
            In::Query => [
                self::Form,
                self::SpaceDelimited,
                self::PipeDelimited,
                self::DeepObject
            ],
            In::Header => [
                self::Simple,
            ],
            In::Cookie => [
                self::Form,
            ]
        }));
    }

    public function isSuitableFor(Type $type): bool
    {
        return match ($this) {
            self::SpaceDelimited,
            self::PipeDelimited => !$type->isPrimitive(),
            self::DeepObject => $type === Type::Object,
            default => true,
        };
    }
}
