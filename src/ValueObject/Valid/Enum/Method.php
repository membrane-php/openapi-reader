<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\ValueObject\Valid\Enum;

enum Method: string
{
    case GET = 'get';
    case PUT = 'put';
    case POST = 'post';
    case DELETE = 'delete';
    case OPTIONS = 'options';
    case HEAD = 'head';
    case PATCH = 'patch';
    case TRACE = 'trace';

    public function isRedundant(): bool
    {
        return in_array($this, [Method::HEAD, Method::OPTIONS, Method::TRACE]);
    }
}
