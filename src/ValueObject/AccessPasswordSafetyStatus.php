<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the password safety status type and its canonical responsibility within the Accessing component.
 */
enum AccessPasswordSafetyStatus: string
{
    case Safe = 'safe';
    case Compromised = 'compromised';
    case Unavailable = 'unavailable';
}
