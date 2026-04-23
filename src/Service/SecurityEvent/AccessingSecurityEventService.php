<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecurityEvent;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\Accessing\ValueObject\SecurityEventSeverity;
use App\Accessing\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessingSecurityEventService implements AccessingSecurityEventServiceInterface
{
    public function __construct(
        private SecurityEventRepositoryInterface $securityEventRepository,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        ?AccessAccountEntity $account = null,
        ?Request $request = null,
        array $context = [],
    ): AccessSecurityEventEntity {
        $securityEvent = new AccessSecurityEventEntity(
            $eventType,
            $severity,
            $account,
            $request?->getClientIp(),
            $request?->headers->get('User-Agent'),
            $context,
        );

        $this->securityEventRepository->save($securityEvent, true);

        return $securityEvent;
    }
}
