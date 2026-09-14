<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the security event severity type and its canonical responsibility within the Accessing component.
 */
enum AccessSecurityEventSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
}
