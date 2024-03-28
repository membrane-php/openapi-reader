<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Enum;

enum In: string
{
    case Path = 'path';
    case Query = 'query';
    case Header = 'header';
    case Cookie = 'cookie';
}
