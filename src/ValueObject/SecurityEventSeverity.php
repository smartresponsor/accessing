<?php

declare(strict_types=1);

namespace App\ValueObject;

enum SecurityEventSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
}
