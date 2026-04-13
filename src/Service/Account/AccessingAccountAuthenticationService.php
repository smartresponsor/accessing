<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Dto\AccessingSignInResultDto;
use App\Entity\Account;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\ServiceInterface\AccountSession\AccessingAccountSessionServiceInterface;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ValueObject\EmailAddress;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final readonly class AccessingAccountAuthenticationService implements AccessingAccountAuthenticationServiceInterface
{
    public const string PendingSecondFactorSessionKey = 'accessing.pending_second_factor_account_id';
    private const string FirewallName = 'main';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccessingCredentialServiceInterface $credentialService,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private AccessingAccountSessionServiceInterface $accountSessionService,
        private TokenStorageInterface $tokenStorage,
        private RateLimiterFactory $accessingSignInLimiter,
        private int $accessingAccountLockThreshold,
        private int $accessingAccountLockMinutes,
    ) {}

    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessingSignInResultDto
    {
        $normalizedEmailAddress = new EmailAddress($emailAddress);
        $limiter = $this->accessingSignInLimiter->create(sprintf('%s|%s', $normalizedEmailAddress, $request->getClientIp() ?? 'unknown'));

        if (!$limiter->consume(1)->isAccepted()) {
            return AccessingSignInResultDto::failed('Too many sign-in attempts. Please wait before trying again.');
        }

        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (!$account instanceof Account) {
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
            $request->getSession()->set(self::PendingSecondFactorSessionKey, $account->getId());
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

    public function completePendingSecondFactor(Account $account, Request $request): void
    {
        $this->signIn($account, $request);
    }

    public function signOut(?Account $account, Request $request): void
    {
        $session = $request->getSession();

        if ($account instanceof Account) {
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
        $pendingAccountId = $session->get(self::PendingSecondFactorSessionKey);

        return is_int($pendingAccountId) ? $pendingAccountId : null;
    }

    public function clearPendingSecondFactor(SessionInterface $session): void
    {
        $session->remove(self::PendingSecondFactorSessionKey);
    }

    private function signIn(Account $account, Request $request): void
    {
        $session = $request->getSession();
        $session->migrate(true);
        $this->clearPendingSecondFactor($session);

        $account->markSuccessfulSignIn();
        $account->unlock();
        $this->accountRepository->save($account, true);

        $token = new PostAuthenticationToken($account, self::FirewallName, $account->getRoles());
        $this->tokenStorage->setToken($token);
        $session->set('_security_' . self::FirewallName, serialize($token));

        $this->accountSessionService->registerSession($account, $request);
        $this->securityEventService->record(
            SecurityEventType::SignInSucceeded,
            SecurityEventSeverity::Info,
            $account,
            $request,
        );
    }
}
