<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Account;

use App\Accessing\Dto\AccessingSignInResultDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\AccountSession\AccessingAccountSessionServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\Accessing\ValueObject\EmailAddress;
use App\Accessing\ValueObject\SecurityEventSeverity;
use App\Accessing\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final readonly class AccessingAccountAuthenticationService implements AccessingAccountAuthenticationServiceInterface
{
    public const string PENDING_SECOND_FACTOR_SESSION_KEY = 'accessing.pending_second_factor_account_id';
    private const string FIREWALL_NAME = 'main';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccessingCredentialServiceInterface $credentialService,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private AccessingAccountSessionServiceInterface $accountSessionService,
        private TokenStorageInterface $tokenStorage,
        private RateLimiterFactory $accessingSignInLimiter,
        private int $accessingAccountLockThreshold,
        private int $accessingAccountLockMinutes,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessingSignInResultDto
    {
        $normalizedEmailAddress = new EmailAddress($emailAddress);
        $limiter = $this->accessingSignInLimiter->create(sprintf('%s|%s', $normalizedEmailAddress, $request->getClientIp() ?? 'unknown'));

        if (!$limiter->consume()->isAccepted()) {
            return AccessingSignInResultDto::failed('Too many sign-in attempts. Please wait before trying again.');
        }

        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (!$account instanceof AccessAccountEntity) {
            $this->securityEventService->record(
                SecurityEventType::SignInFailed,
                SecurityEventSeverity::Warning,
                null,
                $request,
                ['emailAddress' => $normalizedEmailAddress->toString(), 'reason' => 'account_not_found'],
            );

            return AccessingSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($account->getLockedUntil() instanceof \DateTimeImmutable && !$account->isLocked()) {
            $account->unlock();
        }

        if ($account->isLocked()) {
            return AccessingSignInResultDto::failed(sprintf(
                'This account is locked until %s.',
                $account->getLockedUntil()?->format('Y-m-d H:i'),
            ));
        }

        if (!$this->credentialService->verifyPassword($account, $plainPassword)) {
            $account->registerFailedSignInAttempt();

            if ($account->getFailedSignInCount() >= $this->accessingAccountLockThreshold) {
                $account->lockUntil(new \DateTimeImmutable(sprintf('+%d minutes', $this->accessingAccountLockMinutes)));
                $this->securityEventService->record(
                    SecurityEventType::AccountLocked,
                    SecurityEventSeverity::Critical,
                    $account,
                    $request,
                    ['failedSignInCount' => $account->getFailedSignInCount()],
                );
            } else {
                $this->securityEventService->record(
                    SecurityEventType::SignInFailed,
                    SecurityEventSeverity::Warning,
                    $account,
                    $request,
                    ['failedSignInCount' => $account->getFailedSignInCount()],
                );
            }

            $this->accountRepository->save($account, true);

            return AccessingSignInResultDto::failed('Invalid sign-in credentials.');
        }

        if ($account->getSecondFactor()?->isEnabled()) {
            $request->getSession()->set(self::PENDING_SECOND_FACTOR_SESSION_KEY, $account->getId());
            $this->securityEventService->record(
                SecurityEventType::SecondFactorChallenged,
                SecurityEventSeverity::Info,
                $account,
                $request,
            );

            return AccessingSignInResultDto::pendingSecondFactor($account);
        }

        $this->signIn($account, $request);

        return AccessingSignInResultDto::authenticated($account);
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
                SecurityEventType::SessionInvalidated,
                SecurityEventSeverity::Info,
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
            SecurityEventType::SignInSucceeded,
            SecurityEventSeverity::Info,
            $account,
            $request,
        );
    }
}
