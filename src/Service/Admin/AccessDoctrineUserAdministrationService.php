<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\Repository\AccessUserRepository;
use App\Accessing\Repository\AccessUserSessionRepository;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * First executable Accessing-owned implementation for controlled user actions.
 *
 * Administering may request these actions, but Accessing remains the owner of
 * user, session, 2FA, and security semantics.
 */
final class AccessDoctrineUserAdministrationService implements AccessUserAdministrationServiceInterface
{
    private const SUPPORTED_ACTIONS = [
        'accessing.user.lock',
        'accessing.user.unlock',
        'accessing.session.terminate',
        'accessing.2fa.reset.request',
        'accessing.security_events.view',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessUserRepository $userRepository,
        private readonly AccessUserSessionRepository $userSessionRepository,
    ) {
    }

    public function supports(string $action): bool
    {
        return in_array($action, self::SUPPORTED_ACTIONS, true);
    }

    public function execute(AccessUserAdministrationAction $action): void
    {
        if (!$this->supports($action->action())) {
            throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action()));
        }

        match ($action->action()) {
            'accessing.user.lock' => $this->lockUser($action),
            'accessing.user.unlock' => $this->unlockUser($action),
            'accessing.session.terminate' => $this->terminateSession($action),
            'accessing.2fa.reset.request' => $this->requestSecondFactorReset($action),
            'accessing.security_events.view' => null,
            default => throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action())),
        };
    }

    private function lockUser(AccessUserAdministrationAction $action): void
    {
        $user = $this->requireUser($action->userReference());
        $user->lock();
        $this->entityManager->flush();
    }

    private function unlockUser(AccessUserAdministrationAction $action): void
    {
        $user = $this->requireUser($action->userReference());
        $user->unlock();
        $this->entityManager->flush();
    }

    private function terminateSession(AccessUserAdministrationAction $action): void
    {
        $sessionIdentifier = self::stringValue($action->safeContext()['session_identifier'] ?? null);
        if ('' === trim($sessionIdentifier)) {
            throw new \InvalidArgumentException('Session termination requires safeContext.session_identifier.');
        }

        $session = $this->userSessionRepository->findOneBySessionIdentifier($sessionIdentifier);
        if (null === $session) {
            throw new \InvalidArgumentException('Accessing user session was not found.');
        }

        $session->revoke();
        $this->entityManager->flush();
    }

    private function requestSecondFactorReset(AccessUserAdministrationAction $action): void
    {
        $user = $this->requireUser($action->userReference());
        $secondFactor = $user->getSecondFactor();
        if (null !== $secondFactor) {
            $secondFactor->revoke();
        }
        $user->setTotpSecret(null);
        $user->setSecondFactorEnabled(false);
        $this->entityManager->flush();
    }

    private function requireUser(string $userReference): \App\Accessing\Entity\AccessUserEntity
    {
        $userReference = trim($userReference);
        $user = ctype_digit($userReference)
            ? $this->userRepository->findById((int) $userReference)
            : $this->userRepository->findOneByEmailAddress($userReference);

        if (null === $user) {
            throw new \InvalidArgumentException('Accessing user was not found.');
        }

        return $user;
    }

    private static function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || null === $value) {
            return (string) ($value ?? '');
        }

        return '';
    }
}
