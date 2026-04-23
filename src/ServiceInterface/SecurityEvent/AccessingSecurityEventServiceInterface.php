<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityEvent;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\ValueObject\SecurityEventSeverity;
use App\Accessing\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;

interface AccessingSecurityEventServiceInterface
{
    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        ?AccessAccountEntity $account = null,
        ?Request $request = null,
        array $context = [],
    ): AccessSecurityEventEntity;
}
