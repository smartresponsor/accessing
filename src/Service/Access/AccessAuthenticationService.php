<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Access;

use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final readonly class AccessAuthenticationService implements AccessAuthenticationServiceInterface
{
    public const string PENDING_SECOND_FACTOR_SESSION_KEY = 'accessing.pending_second_factor_user_id';
    private const string FIREWALL_NAME = 'main';

    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessCredentialServiceInterface $credentialService,
        private AccessSecurityEventServiceInterface $securityEventService,
        private AccessSessionServiceInterface $userSessionService,
        private TokenStorageInterface $tokenStorage,
        private RateLimiterFactory $accessingSignInLimiter,
        private int $accessingUserLockThreshold,
        private int $accessingUserLockMinutes,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessSignInResultDto
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $limiter = $this->accessingSignInLimiter->create(sprintf('%s|%s', $normalizedEmailAddress, $request->getClientIp() ?? 'unknown'));

        if (!$limiter->consume()->isAccepted()) {
            return AccessSignInResultDto::failed('Too many sign-in attempts. Please wait before trying again.');
        }

        $user = $this->userRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (!$user instanceof AccessEntity) {
            $this->securityEventService->record(
                AccessSecurityEventType::SignInFailed,
                AccessSecurityEventSeverity::Warning,
                null,
                $request,
                ['emailAddress' => $normalizedEmailAddress->toString(), 'reason' => 'user_not_found'],
            );

            return AccessSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($user->getLockedUntil() instanceof \DateTimeImmutable && !$user->isLocked()) {
            $user->unlock();
        }

        if ($user->isLocked()) {
            return AccessSignInResultDto::failed(sprintf(
                'This user is locked until %s.',
                $user->getLockedUntil()?->format('Y-m-d H:i'),
            ));
        }

        if (!$this->credentialService->verifyPassword($user, $plainPassword)) {
            $user->registerFailedSignInAttempt();

            if ($user->getFailedSignInCount() >= $this->accessingUserLockThreshold) {
                $user->lockUntil(new \DateTimeImmutable(sprintf('+%d minutes', $this->accessingUserLockMinutes)));
                $this->securityEventService->record(
                    AccessSecurityEventType::UserLocked,
                    AccessSecurityEventSeverity::Critical,
                    $user,
                    $request,
                    ['failedSignInCount' => $user->getFailedSignInCount()],
                );
            } else {
                $this->securityEventService->record(
                    AccessSecurityEventType::SignInFailed,
                    AccessSecurityEventSeverity::Warning,
                    $user,
                    $request,
                    ['failedSignInCount' => $user->getFailedSignInCount()],
                );
            }

            $this->userRepository->save($user, true);

            return AccessSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($user->getSecondFactor()?->isEnabled()) {
            $request->getSession()->set(self::PENDING_SECOND_FACTOR_SESSION_KEY, $user->getId());
            $this->securityEventService->record(
                AccessSecurityEventType::SecondFactorChallenged,
                AccessSecurityEventSeverity::Info,
                $user,
                $request,
            );

            return AccessSignInResultDto::pendingSecondFactor($user);
        }

        $this->signIn($user, $request);

        return AccessSignInResultDto::authenticated($user);
    }

    public function completePendingSecondFactor(AccessEntity $user, Request $request): void
    {
        $this->signIn($user, $request);
    }

    public function signOut(?AccessEntity $user, Request $request): void
    {
        $session = $request->getSession();

        if ($user instanceof AccessEntity) {
            $this->userSessionService->invalidateCurrentSession($user, $session);
            $this->securityEventService->record(
                AccessSecurityEventType::SessionInvalidated,
                AccessSecurityEventSeverity::Info,
                $user,
                $request,
                ['sessionIdentifier' => $session->getId()],
            );
        }

        $this->clearPendingSecondFactor($session);
        $this->tokenStorage->setToken(null);
        $session->invalidate();
    }

    public function getPendingSecondFactorUserId(SessionInterface $session): ?int
    {
        $pendingUserId = $session->get(self::PENDING_SECOND_FACTOR_SESSION_KEY);

        return is_int($pendingUserId) ? $pendingUserId : null;
    }

    public function clearPendingSecondFactor(SessionInterface $session): void
    {
        $session->remove(self::PENDING_SECOND_FACTOR_SESSION_KEY);
    }

    private function signIn(AccessEntity $user, Request $request): void
    {
        $session = $request->getSession();
        $session->migrate(true);
        $this->clearPendingSecondFactor($session);

        $user->markSuccessfulSignIn();
        $user->unlock();
        $this->userRepository->save($user, true);

        $token = new PostAuthenticationToken($user, self::FIREWALL_NAME, $user->getRoles());
        $this->tokenStorage->setToken($token);
        $session->set('_security_'.self::FIREWALL_NAME, serialize($token));

        $this->userSessionService->registerSession($user, $request);
        $this->securityEventService->record(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
        );
    }
}
