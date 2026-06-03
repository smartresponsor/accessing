<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Verification;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessVerificationChallengeRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\SecurityNotification\AccessSecurityNotificationServiceInterface;
use App\Accessing\ServiceInterface\Vendor\AccessPhoneVerificationProviderServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class AccessVerificationChallengeService implements AccessVerificationChallengeServiceInterface
{
    public function __construct(
        private AccessVerificationChallengeRepositoryInterface $verificationChallengeRepository,
        private AccessAccountRepositoryInterface $accountRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
        private AccessPhoneVerificationProviderServiceInterface $phoneVerificationProvider,
        private AccessSecurityNotificationServiceInterface $securityNotificationService,
        private string $appSecret,
        private int $accessingVerificationCodeTtlMinutes,
        private int $accessingRecoveryCodeTtlMinutes,
    ) {
    }

    /**
     * Issue a fresh email verification challenge and dispatch notification.
     *
     * @throws \DateMalformedStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function issueEmailVerification(AccessAccountEntity $account, ?Request $request = null): AccessIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $account,
            AccessVerificationChallengeType::EmailVerification,
            $account->getEmailAddress(),
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        $this->securityNotificationService->sendEmailVerificationCode(
            $account,
            $issuedChallenge->plainCode,
            $this->accessingVerificationCodeTtlMinutes,
        );

        $this->securityEventService->record(
            AccessSecurityEventType::EmailVerificationRequested,
            AccessSecurityEventSeverity::Info,
            $account,
            $request,
            ['destination' => $account->getEmailAddress()],
        );

        return $issuedChallenge;
    }

    /**
     * Issue a phone verification challenge for the supplied phone number.
     *
     * @throws \DateMalformedStringException
     * @throws RandomException
     */
    public function issuePhoneVerification(AccessAccountEntity $account, string $phoneNumber, ?Request $request = null): AccessIssuedChallengeDto
    {
        $account->changePhoneNumber($phoneNumber);

        $issuedChallenge = $this->issueChallenge(
            $account,
            AccessVerificationChallengeType::PhoneVerification,
            $phoneNumber,
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        $this->phoneVerificationProvider->sendVerificationMessage(
            $phoneNumber,
            sprintf('Accessing phone verification code: %s', $issuedChallenge->plainCode),
        );

        $this->securityEventService->record(
            AccessSecurityEventType::PhoneVerificationRequested,
            AccessSecurityEventSeverity::Info,
            $account,
            $request,
            ['destination' => $phoneNumber],
        );

        $this->accountRepository->save($account, true);

        return $issuedChallenge;
    }

    /**
     * Issue a password recovery challenge for the account.
     *
     * @throws \DateMalformedStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function issuePasswordRecovery(AccessAccountEntity $account, ?Request $request = null): AccessIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $account,
            AccessVerificationChallengeType::PasswordRecovery,
            $account->getEmailAddress(),
            $request,
            $this->accessingRecoveryCodeTtlMinutes,
        );

        $this->securityNotificationService->sendPasswordRecoveryCode(
            $account,
            $issuedChallenge->plainCode,
            $this->accessingRecoveryCodeTtlMinutes,
        );

        $this->securityEventService->record(
            AccessSecurityEventType::RecoveryRequested,
            AccessSecurityEventSeverity::Warning,
            $account,
            $request,
        );

        return $issuedChallenge;
    }

    /**
     * Complete email verification when a valid challenge code is provided.
     */
    public function completeEmailVerification(AccessAccountEntity $account, string $code): bool
    {
        if (!$this->consumeChallenge($account, AccessVerificationChallengeType::EmailVerification, $code)) {
            return false;
        }

        $account->markEmailVerified();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(AccessSecurityEventType::EmailVerified, AccessSecurityEventSeverity::Info, $account);

        return true;
    }

    /**
     * Complete phone verification when a valid challenge code is provided.
     */
    public function completePhoneVerification(AccessAccountEntity $account, string $code): bool
    {
        if (!$this->consumeChallenge($account, AccessVerificationChallengeType::PhoneVerification, $code)) {
            return false;
        }

        $account->markPhoneVerified();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(AccessSecurityEventType::PhoneVerified, AccessSecurityEventSeverity::Info, $account);

        return true;
    }

    /**
     * Consume password recovery challenge with a one-time code.
     */
    public function consumePasswordRecovery(AccessAccountEntity $account, string $code): bool
    {
        return $this->consumeChallenge($account, AccessVerificationChallengeType::PasswordRecovery, $code);
    }

    /**
     * Clean up expired and stale verification challenges.
     */
    public function cleanupExpiredChallenges(): int
    {
        return $this->verificationChallengeRepository->cleanupExpiredConsumedBefore(
            new \DateTimeImmutable('-2 days'),
        );
    }

    /**
     * @throws \DateMalformedStringException
     * @throws RandomException
     */
    private function issueChallenge(
        AccessAccountEntity $account,
        AccessVerificationChallengeType $challengeType,
        string $destination,
        ?Request $request,
        int $ttlMinutes,
    ): AccessIssuedChallengeDto {
        $plainCode = (string) random_int(100000, 999999);

        $verificationChallenge = new AccessVerificationChallengeEntity(
            $account,
            $challengeType,
            $destination,
            $this->hashCode($plainCode),
            new \DateTimeImmutable(sprintf('+%d minutes', $ttlMinutes)),
            $request?->getClientIp(),
        );

        $account->addVerificationChallenge($verificationChallenge);
        $this->verificationChallengeRepository->save($verificationChallenge, true);

        return new AccessIssuedChallengeDto($verificationChallenge, $plainCode);
    }

    private function consumeChallenge(AccessAccountEntity $account, AccessVerificationChallengeType $challengeType, string $code): bool
    {
        $verificationChallenge = $this->verificationChallengeRepository->findLatestActiveForAccount($account, $challengeType);

        if (!$verificationChallenge instanceof AccessVerificationChallengeEntity) {
            return false;
        }

        $verificationChallenge->registerAttempt();

        if (!hash_equals($verificationChallenge->getCodeHash(), $this->hashCode(trim($code)))) {
            $this->verificationChallengeRepository->save($verificationChallenge, true);

            return false;
        }

        $verificationChallenge->consume();
        $this->verificationChallengeRepository->save($verificationChallenge, true);

        return true;
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, $this->appSecret);
    }
}
