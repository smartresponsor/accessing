<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum AccessPasskeyCeremonyPurpose: string
{
    case Registration = 'registration';
    case Authentication = 'authentication';
}
