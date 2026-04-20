<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecurityEvent;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\SecurityEvent;
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
        ?Account $account = null,
        ?Request $request = null,
        array $context = [],
    ): SecurityEvent {
        $securityEvent = new SecurityEvent(
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
