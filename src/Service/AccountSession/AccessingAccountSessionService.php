<?php

declare(strict_types=1);

namespace App\Service\AccountSession;

use App\Entity\Account;
use App\Entity\AccountSession;
use App\RepositoryInterface\AccountSessionRepositoryInterface;
use App\ServiceInterface\AccountSession\AccessingAccountSessionServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class AccessingAccountSessionService implements AccessingAccountSessionServiceInterface
{
    public function __construct(
        private AccountSessionRepositoryInterface $accountSessionRepository,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private int $accessingSessionMaxIdleDays,
    ) {}

    /**
     * Ensure the current request session is registered and marked as active.
     */
    public function registerSession(Account $account, Request $request): void
    {
        $session = $request->getSession();
        $sessionIdentifier = $session->getId();
        $accountSession = $this->accountSessionRepository->findOneBySessionIdentifier($sessionIdentifier);

        if (!$accountSession instanceof AccountSession) {
            $accountSession = new AccountSession(
                $account,
                $sessionIdentifier,
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            );

            $account->addAccountSession($accountSession);
            $this->accountSessionRepository->save($accountSession);
        }

        $accountSession->touch();
        $this->accountSessionRepository->save($accountSession, true);

        $this->securityEventService->record(
            SecurityEventType::SessionRegistered,
            SecurityEventSeverity::Info,
            $account,
            $request,
            ['sessionIdentifier' => $sessionIdentifier],
        );
    }

    /**
     * Invalidate the currently active session for the account when it belongs to the same account.
     */
    public function invalidateCurrentSession(Account $account, SessionInterface $session): void
    {
        $accountSession = $this->accountSessionRepository->findOneBySessionIdentifier($session->getId());

        if ($accountSession instanceof AccountSession && $accountSession->getAccount() === $account) {
            $accountSession->invalidate();
            $this->accountSessionRepository->save($accountSession, true);
        }
    }

    /**
     * Invalidate all active sessions except the current one.
     */
    public function invalidateOtherSessions(Account $account, SessionInterface $session): int
    {
        return $this->accountSessionRepository->invalidateOtherActiveSessions($account, $session->getId());
    }

    /**
     * Remove invalidated sessions older than configured retention.
     */
    public function cleanupSessions(): int
    {
        return $this->accountSessionRepository->cleanupInvalidatedBefore(
            new \DateTimeImmutable(sprintf('-%d days', $this->accessingSessionMaxIdleDays)),
        );
    }
}
