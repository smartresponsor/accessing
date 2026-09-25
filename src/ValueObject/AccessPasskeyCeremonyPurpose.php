<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the passkey ceremony purpose type and its canonical responsibility within the Accessing component.
 */
enum AccessPasskeyCeremonyPurpose: string
{
    case Registration = 'registration';
    case Authentication = 'authentication';
}
