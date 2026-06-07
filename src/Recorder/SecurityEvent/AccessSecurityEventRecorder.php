<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Recorder\SecurityEvent;

use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AccessSecurityEventRecorder implements AccessSecurityEventRecorderInterface
{
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private RequestStack $requestStack,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(string $eventType, ?AccessUserEntity $user = null, array $context = []): AccessSecurityEventEntity
    {
        $request = $this->requestStack->getCurrentRequest();

        $securityEvent = new AccessSecurityEventEntity()
            ->setEventType($eventType)
            ->setUser($user)
            ->setContext($context)
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->securityEventRepository->save($securityEvent, true);

        return $securityEvent;
    }
}
