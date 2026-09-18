<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service;

use App\Accessing\Authenticator\AccessProgrammaticAuthenticator;
use App\Accessing\DTO\AccessSignInResultDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Defines the authentication service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessAuthenticationService implements AccessAuthenticationServiceInterface
{
    public const string PENDING_SECOND_FACTOR_SESSION_KEY = 'accessing.pending_second_factor_user_id';
    private const string FIREWALL_NAME = 'main';

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessCredentialServiceInterface $credentialService,
        private AccessSecurityEventServiceInterface $securityEventService,
        private AccessSessionServiceInterface $userSessionService,
        private Security $security,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private RateLimiterFactory $accessingSignInLimiter,
        private int $accessingUserLockThreshold,
        private int $accessingUserLockMinutes,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessSignInResultDTO
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $limiter = $this->accessingSignInLimiter->create(sprintf('%s|%s', $normalizedEmailAddress, $request->getClientIp() ?? 'unknown'));

        if (!$limiter->consume()->isAccepted()) {
            return AccessSignInResultDTO::failed('Too many sign in attempts. Please wait before trying again.');
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

            return AccessSignInResultDTO::failed('Invalid sign in credentials.');
        }

        if ($user->getLockedUntil() instanceof \DateTimeImmutable && !$user->isLocked()) {
            $user->unlock();
        }

        if ($user->isLocked()) {
            $lockedUntil = $user->getLockedUntil();
            $this->securityEventService->record(
                AccessSecurityEventType::LockedAccountSignInAttempt,
                AccessSecurityEventSeverity::Warning,
                $user,
                $request,
                [
                    'lockExpiresAt' => $lockedUntil?->format(\DateTimeInterface::ATOM),
                    'reason' => 'account_locked',
                ],
            );

            return AccessSignInResultDTO::failed(sprintf(
                'This user is locked until %s.',
                $lockedUntil?->format('Y-m-d H:i'),
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

            return AccessSignInResultDTO::failed('Invalid sign in credentials.');
        }

        if ($user->getSecondFactor()?->isEnabled()) {
            $request->getSession()->set(self::PENDING_SECOND_FACTOR_SESSION_KEY, $user->getId());
            $this->securityEventService->record(
                AccessSecurityEventType::SecondFactorChallenged,
                AccessSecurityEventSeverity::Info,
                $user,
                $request,
            );

            return AccessSignInResultDTO::pendingSecondFactor($user);
        }

        $this->signIn($user, $request);

        return AccessSignInResultDTO::authenticated($user);
    }

    /**
     * Executes the complete pending second factor operation within the canonical Accessing component workflow.
     */
    public function completePendingSecondFactor(AccessEntity $user, Request $request): void
    {
        $this->signIn($user, $request);
    }

    /**
     * Executes the complete mobile second factor operation within the canonical Accessing component workflow.
     */
    public function completeMobileSecondFactor(AccessEntity $user, Request $request): void
    {
        $user->markSuccessfulSignIn();
        $user->unlock();
        $this->userRepository->save($user, true);
        $this->securityEventService->record(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['transport' => 'mobile_token'],
        );
    }

    /**
     * Executes the complete passkey sign in operation within the canonical Accessing component workflow.
     */
    public function completePasskeySignIn(AccessEntity $user, Request $request): void
    {
        $this->signIn($user, $request);
    }

    /**
     * Executes the complete external sign in operation within the canonical Accessing component workflow.
     */
    public function completeExternalSignIn(AccessEntity $user, Request $request): void
    {
        $this->signIn($user, $request);
    }

    /**
     * Executes the sign out operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the get pending second factor user id operation within the canonical Accessing component workflow.
     */
    public function getPendingSecondFactorUserId(SessionInterface $session): ?int
    {
        $pendingUserId = $session->get(self::PENDING_SECOND_FACTOR_SESSION_KEY);

        return is_int($pendingUserId) ? $pendingUserId : null;
    }

    /**
     * Executes the clear pending second factor operation within the canonical Accessing component workflow.
     */
    public function clearPendingSecondFactor(SessionInterface $session): void
    {
        $session->remove(self::PENDING_SECOND_FACTOR_SESSION_KEY);
    }

    /**
     * Executes the sign in operation within the canonical Accessing component workflow.
     */
    private function signIn(AccessEntity $user, Request $request): void
    {
        $session = $request->getSession();
        $session->migrate(true);
        $this->clearPendingSecondFactor($session);

        $user->markSuccessfulSignIn();
        $user->unlock();
        $this->userRepository->save($user, false);

        if (str_starts_with($request->getPathInfo(), '/api/access/')) {
            $this->securityEventService->record(
                AccessSecurityEventType::SignInSucceeded,
                AccessSecurityEventSeverity::Info,
                $user,
                $request,
                ['transport' => 'mobile_token'],
                false,
            );
            $this->userRepository->save($user, true);

            return;
        }

        $pushedRequest = $this->requestStack->getCurrentRequest() !== $request;
        if ($pushedRequest) {
            $this->requestStack->push($request);
        }

        try {
            $this->security->login(
                $user,
                authenticatorName: AccessProgrammaticAuthenticator::class,
                firewallName: self::FIREWALL_NAME,
            );
        } finally {
            if ($pushedRequest) {
                $this->requestStack->pop();
            }
        }

        $this->userSessionService->registerSession($user, $request, false);
        $this->securityEventService->record(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            [],
            false,
        );
        $this->userRepository->save($user, true);
    }
}
