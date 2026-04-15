<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\ServiceInterface\SecurityEvent;

use App\Entity\Account;
use App\Entity\SecurityEvent;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;

interface AccessingSecurityEventServiceInterface
{
    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        ?Account $account = null,
        ?Request $request = null,
        array $context = [],
    ): SecurityEvent;
}
