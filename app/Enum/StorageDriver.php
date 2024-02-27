<?php

declare(strict_types=1);

namespace App\Enum;

enum StorageDriver
{
    case Local;
    case Remote_S3;
}