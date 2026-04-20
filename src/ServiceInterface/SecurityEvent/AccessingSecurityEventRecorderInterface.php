<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityEvent;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\SecurityEvent;

interface AccessingSecurityEventRecorderInterface
{
    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(string $eventType, ?Account $account = null, array $context = []): SecurityEvent;
}
