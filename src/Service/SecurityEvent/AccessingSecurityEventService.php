<?php

declare(strict_types=1);

namespace App\Service\SecurityEvent;

use App\Entity\Account;
use App\Entity\SecurityEvent;
use App\RepositoryInterface\SecurityEventRepositoryInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessingSecurityEventService implements AccessingSecurityEventServiceInterface
{
    public function __construct(
        private SecurityEventRepositoryInterface $securityEventRepository,
    ) {}

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
