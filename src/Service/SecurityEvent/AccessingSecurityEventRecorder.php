<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Service\SecurityEvent;

use App\Entity\Account;
use App\Entity\SecurityEvent;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class AccessingSecurityEventRecorder implements AccessingSecurityEventRecorderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function record(string $eventType, ?Account $account = null, array $context = []): SecurityEvent
    {
        $request = $this->requestStack->getCurrentRequest();

        $securityEvent = (new SecurityEvent())
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
