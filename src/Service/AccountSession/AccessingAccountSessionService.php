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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class AccessingAccountSessionService implements AccessingAccountSessionServiceInterface
{
    public function __construct(
        private AccountSessionRepositoryInterface $accountSessionRepository,
        private EntityManagerInterface $entityManager,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private int $accessingSessionMaxIdleDays,
    ) {}

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
        $this->entityManager->flush();

        $this->securityEventService->record(
            SecurityEventType::SessionRegistered,
            SecurityEventSeverity::Info,
            $account,
            $request,
            ['sessionIdentifier' => $sessionIdentifier],
        );
    }

    public function invalidateCurrentSession(Account $account, SessionInterface $session): void
    {
        $accountSession = $this->accountSessionRepository->findOneBySessionIdentifier($session->getId());

        if ($accountSession instanceof AccountSession && $accountSession->getAccount() === $account) {
            $accountSession->invalidate();
            $this->entityManager->flush();
        }
    }

    public function invalidateOtherSessions(Account $account, SessionInterface $session): int
    {
        return $this->accountSessionRepository->invalidateOtherActiveSessions($account, $session->getId());
    }

    public function cleanupSessions(): int
    {
        return $this->accountSessionRepository->cleanupInvalidatedBefore(
            new \DateTimeImmutable(sprintf('-%d days', $this->accessingSessionMaxIdleDays)),
        );
    }
}
