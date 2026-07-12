<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum AccessPasswordSafetyStatus: string
{
    case Safe = 'safe';
    case Compromised = 'compromised';
    case Unavailable = 'unavailable';
}
