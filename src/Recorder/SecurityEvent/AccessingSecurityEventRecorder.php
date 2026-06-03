<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Recorder\SecurityEvent;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AccessingSecurityEventRecorder implements AccessingSecurityEventRecorderInterface
{
    public function __construct(
        private SecurityEventRepositoryInterface $securityEventRepository,
        private RequestStack $requestStack,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(string $eventType, ?AccessAccountEntity $account = null, array $context = []): AccessSecurityEventEntity
    {
        $request = $this->requestStack->getCurrentRequest();

        $securityEvent = new AccessSecurityEventEntity()
            ->setEventType($eventType)
            ->setAccount($account)
            ->setContext($context)
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->securityEventRepository->save($securityEvent, true);

        return $securityEvent;
    }
}
