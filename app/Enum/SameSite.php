<?php

declare(strict_types=1);

namespace App\Enum;

enum SameSite: string
{
    case STRICT = 'strict';
    case LAX = 'lax';
    case NONE = 'none';
}