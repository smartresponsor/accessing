<?php

declare(strict_types=1);

namespace App\ServiceInterface\SecurityEvent;

use App\Entity\Account;
use App\Entity\SecurityEvent;

interface AccessingSecurityEventRecorderInterface
{
    public function record(string $eventType, ?Account $account = null, array $context = []): SecurityEvent;
}
