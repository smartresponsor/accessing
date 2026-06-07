<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Session;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\RepositoryInterface\AccessSessionRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class AccessSessionService implements AccessSessionServiceInterface
{
    public function __construct(
        private AccessSessionRepositoryInterface $userSessionRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
        private int $accessingSessionMaxIdleDays,
    ) {
    }

    /**
     * Ensure the current request session is registered and marked as active.
     */
    public function registerSession(AccessEntity $user, Request $request): void
    {
        $session = $request->getSession();
        $sessionIdentifier = $session->getId();
        $userSession = $this->userSessionRepository->findOneBySessionIdentifier($sessionIdentifier);

        if (!$userSession instanceof AccessSessionEntity) {
            $userSession = new AccessSessionEntity(
                $user,
                $sessionIdentifier,
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            );

            $user->addUserSession($userSession);
            $this->userSessionRepository->save($userSession);
        }

        $userSession->touch();
        $this->userSessionRepository->save($userSession, true);

        $this->securityEventService->record(
            AccessSecurityEventType::SessionRegistered,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['sessionIdentifier' => $sessionIdentifier],
        );
    }

    /**
     * Invalidate the currently active session for the user when it belongs to the same user.
     */
    public function invalidateCurrentSession(AccessEntity $user, SessionInterface $session): void
    {
        $userSession = $this->userSessionRepository->findOneBySessionIdentifier($session->getId());

        if ($userSession instanceof AccessSessionEntity && $userSession->getUser() === $user) {
            $userSession->invalidate();
            $this->userSessionRepository->save($userSession, true);
        }
    }

    /**
     * Invalidate all active sessions except the current one.
     */
    public function invalidateOtherSessions(AccessEntity $user, SessionInterface $session): int
    {
        return $this->userSessionRepository->invalidateOtherActiveSessions($user, $session->getId());
    }

    /**
     * Remove invalidated sessions older than configured retention.
     *
     * @throws \DateMalformedStringException
     */
    public function cleanupSessions(): int
    {
        return $this->userSessionRepository->cleanupInvalidatedBefore(
            new \DateTimeImmutable(sprintf('-%d days', $this->accessingSessionMaxIdleDays)),
        );
    }
}
