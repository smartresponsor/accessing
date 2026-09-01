<?php

declare(strict_types=1);

namespace App\Accessing\Recorder\SecurityEvent;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\RecorderInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

/** @deprecated Use AccessSecurityEventServiceInterface directly. */
final readonly class AccessSecurityEventRecorder implements AccessSecurityEventRecorderInterface
{
    public function __construct(private AccessSecurityEventServiceInterface $securityEventService)
    {
    }

    public function record(
        AccessSecurityEventType $eventType,
        AccessSecurityEventSeverity $severity,
        ?AccessEntity $user = null,
        ?Request $request = null,
        array $context = [],
    ): AccessSecurityEventEntity {
        return $this->securityEventService->record($eventType, $severity, $user, $request, $context);
    }
}
