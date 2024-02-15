<?php

namespace App\Enum;

enum StorageDriver
{
    case Local;
    case Remote_S3;
}