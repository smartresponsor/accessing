<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Account;

use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\AccountSession\AccessAccountSessionServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final readonly class AccessAccountAuthenticationService implements AccessAccountAuthenticationServiceInterface
{
    public const string PENDING_SECOND_FACTOR_SESSION_KEY = 'accessing.pending_second_factor_account_id';
    private const string FIREWALL_NAME = 'main';

    public function __construct(
        private AccessAccountRepositoryInterface $accountRepository,
        private AccessCredentialServiceInterface $credentialService,
        private AccessSecurityEventServiceInterface $securityEventService,
        private AccessAccountSessionServiceInterface $accountSessionService,
        private TokenStorageInterface $tokenStorage,
        private RateLimiterFactory $accessingSignInLimiter,
        private int $accessingAccountLockThreshold,
        private int $accessingAccountLockMinutes,
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

        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (!$account instanceof AccessAccountEntity) {
            $this->securityEventService->record(
                AccessSecurityEventType::SignInFailed,
                AccessSecurityEventSeverity::Warning,
                null,
                $request,
                ['emailAddress' => $normalizedEmailAddress->toString(), 'reason' => 'account_not_found'],
            );

            return AccessSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($account->getLockedUntil() instanceof \DateTimeImmutable && !$account->isLocked()) {
            $account->unlock();
        }

        if ($account->isLocked()) {
            return AccessSignInResultDto::failed(sprintf(
                'This account is locked until %s.',
                $account->getLockedUntil()?->format('Y-m-d H:i'),
            ));
        }

        if (!$this->credentialService->verifyPassword($account, $plainPassword)) {
            $account->registerFailedSignInAttempt();

            if ($account->getFailedSignInCount() >= $this->accessingAccountLockThreshold) {
                $account->lockUntil(new \DateTimeImmutable(sprintf('+%d minutes', $this->accessingAccountLockMinutes)));
                $this->securityEventService->record(
                    AccessSecurityEventType::AccountLocked,
                    AccessSecurityEventSeverity::Critical,
                    $account,
                    $request,
                    ['failedSignInCount' => $account->getFailedSignInCount()],
                );
            } else {
                $this->securityEventService->record(
                    AccessSecurityEventType::SignInFailed,
                    AccessSecurityEventSeverity::Warning,
                    $account,
                    $request,
                    ['failedSignInCount' => $account->getFailedSignInCount()],
                );
            }

            $this->accountRepository->save($account, true);

            return AccessSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($account->getSecondFactor()?->isEnabled()) {
            $request->getSession()->set(self::PENDING_SECOND_FACTOR_SESSION_KEY, $account->getId());
            $this->securityEventService->record(
                AccessSecurityEventType::SecondFactorChallenged,
                AccessSecurityEventSeverity::Info,
                $account,
                $request,
            );

            return AccessSignInResultDto::pendingSecondFactor($account);
        }

        $this->signIn($account, $request);

        return AccessSignInResultDto::authenticated($account);
    }

    public function completePendingSecondFactor(AccessAccountEntity $account, Request $request): void
    {
        $this->signIn($account, $request);
    }

    public function signOut(?AccessAccountEntity $account, Request $request): void
    {
        $session = $request->getSession();

        if ($account instanceof AccessAccountEntity) {
            $this->accountSessionService->invalidateCurrentSession($account, $session);
            $this->securityEventService->record(
                AccessSecurityEventType::SessionInvalidated,
                AccessSecurityEventSeverity::Info,
                $account,
                $request,
                ['sessionIdentifier' => $session->getId()],
            );
        }

        $this->clearPendingSecondFactor($session);
        $this->tokenStorage->setToken(null);
        $session->invalidate();
    }

    public function getPendingSecondFactorAccountId(SessionInterface $session): ?int
    {
        $pendingAccountId = $session->get(self::PENDING_SECOND_FACTOR_SESSION_KEY);

        return is_int($pendingAccountId) ? $pendingAccountId : null;
    }

    public function clearPendingSecondFactor(SessionInterface $session): void
    {
        $session->remove(self::PENDING_SECOND_FACTOR_SESSION_KEY);
    }

    private function signIn(AccessAccountEntity $account, Request $request): void
    {
        $session = $request->getSession();
        $session->migrate(true);
        $this->clearPendingSecondFactor($session);

        $account->markSuccessfulSignIn();
        $account->unlock();
        $this->accountRepository->save($account, true);

        $token = new PostAuthenticationToken($account, self::FIREWALL_NAME, $account->getRoles());
        $this->tokenStorage->setToken($token);
        $session->set('_security_'.self::FIREWALL_NAME, serialize($token));

        $this->accountSessionService->registerSession($account, $request);
        $this->securityEventService->record(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $account,
            $request,
        );
    }
}
