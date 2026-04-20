<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecurityEvent;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\SecurityEvent;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AccessingSecurityEventRecorder implements AccessingSecurityEventRecorderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(string $eventType, ?Account $account = null, array $context = []): SecurityEvent
    {
        $request = $this->requestStack->getCurrentRequest();

        $securityEvent = new SecurityEvent()
            ->setEventType($eventType)
            ->setAccount($account)
            ->setContext($context)
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->entityManager->persist($securityEvent);
        $this->entityManager->flush();

        return $securityEvent;
    }
}
