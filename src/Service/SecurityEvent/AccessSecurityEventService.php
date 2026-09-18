<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecurityEvent;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Factory\SecurityEvent\AccessSecurityEventContextFactory;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the security event service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSecurityEventService implements AccessSecurityEventServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessSecurityEventContextFactory $contextFactory,
    ) {
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function record(
        AccessSecurityEventType $eventType,
        AccessSecurityEventSeverity $severity,
        ?AccessEntity $user = null,
        ?Request $request = null,
        array $context = [],
        bool $flush = true,
    ): AccessSecurityEventEntity {
        $requestContext = $this->contextFactory->fromRequest($request);
        $securityEvent = new AccessSecurityEventEntity(
            $eventType,
            $severity,
            $user,
            $requestContext['ipAddress'],
            $requestContext['userAgent'],
            $context,
        );

        $this->securityEventRepository->save($securityEvent, $flush);

        return $securityEvent;
    }
}
