<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader;

enum Style: string
{
    case Matrix = 'matrix';
    case Label = 'label';
    case Form = 'form';
    case Simple = 'simple';
    case SpaceDelimited = 'spaceDelimited';
    case PipeDelimited = 'pipeDelimited';
    case DeepObject = 'deepObject';
}
