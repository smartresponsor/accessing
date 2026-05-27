<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\Repository\AccountRepository;
use App\Accessing\Repository\AccountSessionRepository;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * First executable Accessing-owned implementation for controlled account actions.
 *
 * Administering may request these actions, but Accessing remains the owner of
 * account, session, 2FA, and security semantics.
 */
final class DoctrineAccessingAccountAdministrationService implements AccessingAccountAdministrationServiceInterface
{
    private const SUPPORTED_ACTIONS = [
        'accessing.account.lock',
        'accessing.account.unlock',
        'accessing.session.terminate',
        'accessing.2fa.reset.request',
        'accessing.security_events.view',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $accountRepository,
        private readonly AccountSessionRepository $accountSessionRepository,
    ) {
    }

    public function supports(string $action): bool
    {
        return in_array($action, self::SUPPORTED_ACTIONS, true);
    }

    public function execute(AccessingAccountAdministrationAction $action): void
    {
        if (!$this->supports($action->action())) {
            throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action()));
        }

        match ($action->action()) {
            'accessing.account.lock' => $this->lockAccount($action),
            'accessing.account.unlock' => $this->unlockAccount($action),
            'accessing.session.terminate' => $this->terminateSession($action),
            'accessing.2fa.reset.request' => $this->requestSecondFactorReset($action),
            'accessing.security_events.view' => null,
            default => throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action())),
        };
    }

    private function lockAccount(AccessingAccountAdministrationAction $action): void
    {
        $account = $this->requireAccount($action->accountReference());
        $account->lock();
        $this->entityManager->flush();
    }

    private function unlockAccount(AccessingAccountAdministrationAction $action): void
    {
        $account = $this->requireAccount($action->accountReference());
        $account->unlock();
        $this->entityManager->flush();
    }

    private function terminateSession(AccessingAccountAdministrationAction $action): void
    {
        $sessionIdentifier = (string) ($action->safeContext()['session_identifier'] ?? '');
        if ('' === trim($sessionIdentifier)) {
            throw new \InvalidArgumentException('Session termination requires safeContext.session_identifier.');
        }

        $session = $this->accountSessionRepository->findOneBySessionIdentifier($sessionIdentifier);
        if (null === $session) {
            throw new \InvalidArgumentException('Accessing account session was not found.');
        }

        $session->revoke();
        $this->entityManager->flush();
    }

    private function requestSecondFactorReset(AccessingAccountAdministrationAction $action): void
    {
        $account = $this->requireAccount($action->accountReference());
        $secondFactor = $account->getSecondFactor();
        if (null !== $secondFactor) {
            $secondFactor->revoke();
        }
        $account->setTotpSecret(null);
        $account->setSecondFactorEnabled(false);
        $this->entityManager->flush();
    }

    private function requireAccount(string $accountReference): \App\Accessing\Entity\AccessAccountEntity
    {
        $accountReference = trim($accountReference);
        $account = ctype_digit($accountReference)
            ? $this->accountRepository->findById((int) $accountReference)
            : $this->accountRepository->findOneByEmailAddress($accountReference);

        if (null === $account) {
            throw new \InvalidArgumentException('Accessing account was not found.');
        }

        return $account;
    }
}
