<?php

declare(strict_types=1);

namespace App\Accessing\RecorderInterface\SecurityEvent;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

/** @deprecated Use AccessSecurityEventServiceInterface directly. */
interface AccessSecurityEventRecorderInterface
{
    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        AccessSecurityEventType $eventType,
        AccessSecurityEventSeverity $severity,
        ?AccessEntity $user = null,
        ?Request $request = null,
        array $context = [],
    ): AccessSecurityEventEntity;
}
