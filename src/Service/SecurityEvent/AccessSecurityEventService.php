<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecurityEvent;

use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessSecurityEventService implements AccessSecurityEventServiceInterface
{
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        AccessSecurityEventType $eventType,
        AccessSecurityEventSeverity $severity,
        ?AccessUserEntity $user = null,
        ?Request $request = null,
        array $context = [],
    ): AccessSecurityEventEntity {
        $securityEvent = new AccessSecurityEventEntity(
            $eventType,
            $severity,
            $user,
            $request?->getClientIp(),
            $request?->headers->get('User-Agent'),
            $context,
        );

        $this->securityEventRepository->save($securityEvent, true);

        return $securityEvent;
    }
}
