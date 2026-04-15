<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Service\Verification;

use App\Dto\AccessingIssuedChallengeDto;
use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\RepositoryInterface\VerificationChallengeRepositoryInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ServiceInterface\Vendor\AccessingPhoneVerificationProviderServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use App\ValueObject\VerificationChallengeType;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class AccessingVerificationChallengeService implements AccessingVerificationChallengeServiceInterface
{
    public function __construct(
        private VerificationChallengeRepositoryInterface $verificationChallengeRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private AccessingPhoneVerificationProviderServiceInterface $phoneVerificationProvider,
        private MailerInterface $mailer,
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
    public function issueEmailVerification(Account $account, ?Request $request = null): AccessingIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $account,
            VerificationChallengeType::EmailVerification,
            $account->getEmailAddress(),
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        $this->mailer->send(new Email()
            ->from('no-reply@accessing.local')
            ->to($account->getEmailAddress())
            ->subject('Accessing email verification code')
            ->text(sprintf(
                "Hello %s,\n\nYour Accessing email verification code is %s.\n\nThis code will expire in %d minutes.",
                $account->getDisplayName(),
                $issuedChallenge->plainCode,
                $this->accessingVerificationCodeTtlMinutes,
            )));

        $this->securityEventService->record(
            SecurityEventType::EmailVerificationRequested,
            SecurityEventSeverity::Info,
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
    public function issuePhoneVerification(Account $account, string $phoneNumber, ?Request $request = null): AccessingIssuedChallengeDto
    {
        $account->changePhoneNumber($phoneNumber);

        $issuedChallenge = $this->issueChallenge(
            $account,
            VerificationChallengeType::PhoneVerification,
            $phoneNumber,
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        $this->phoneVerificationProvider->sendVerificationMessage(
            $phoneNumber,
            sprintf('Accessing phone verification code: %s', $issuedChallenge->plainCode),
        );

        $this->securityEventService->record(
            SecurityEventType::PhoneVerificationRequested,
            SecurityEventSeverity::Info,
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
    public function issuePasswordRecovery(Account $account, ?Request $request = null): AccessingIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $account,
            VerificationChallengeType::PasswordRecovery,
            $account->getEmailAddress(),
            $request,
            $this->accessingRecoveryCodeTtlMinutes,
        );

        $this->mailer->send(new Email()
            ->from('no-reply@accessing.local')
            ->to($account->getEmailAddress())
            ->subject('Accessing password recovery code')
            ->text(sprintf(
                "Hello %s,\n\nYour Accessing password recovery code is %s.\n\nThis code will expire in %d minutes.",
                $account->getDisplayName(),
                $issuedChallenge->plainCode,
                $this->accessingRecoveryCodeTtlMinutes,
            )));

        $this->securityEventService->record(
            SecurityEventType::RecoveryRequested,
            SecurityEventSeverity::Warning,
            $account,
            $request,
        );

        return $issuedChallenge;
    }

    /**
     * Complete email verification when a valid challenge code is provided.
     */
    public function completeEmailVerification(Account $account, string $code): bool
    {
        if (!$this->consumeChallenge($account, VerificationChallengeType::EmailVerification, $code)) {
            return false;
        }

        $account->markEmailVerified();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(SecurityEventType::EmailVerified, SecurityEventSeverity::Info, $account);

        return true;
    }

    /**
     * Complete phone verification when a valid challenge code is provided.
     */
    public function completePhoneVerification(Account $account, string $code): bool
    {
        if (!$this->consumeChallenge($account, VerificationChallengeType::PhoneVerification, $code)) {
            return false;
        }

        $account->markPhoneVerified();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(SecurityEventType::PhoneVerified, SecurityEventSeverity::Info, $account);

        return true;
    }

    /**
     * Consume password recovery challenge with a one-time code.
     */
    public function consumePasswordRecovery(Account $account, string $code): bool
    {
        return $this->consumeChallenge($account, VerificationChallengeType::PasswordRecovery, $code);
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
        Account $account,
        VerificationChallengeType $challengeType,
        string $destination,
        ?Request $request,
        int $ttlMinutes,
    ): AccessingIssuedChallengeDto {
        $plainCode = (string) random_int(100000, 999999);

        $verificationChallenge = new VerificationChallenge(
            $account,
            $challengeType,
            $destination,
            $this->hashCode($plainCode),
            new \DateTimeImmutable(sprintf('+%d minutes', $ttlMinutes)),
            $request?->getClientIp(),
        );

        $account->addVerificationChallenge($verificationChallenge);
        $this->verificationChallengeRepository->save($verificationChallenge, true);

        return new AccessingIssuedChallengeDto($verificationChallenge, $plainCode);
    }

    private function consumeChallenge(Account $account, VerificationChallengeType $challengeType, string $code): bool
    {
        $verificationChallenge = $this->verificationChallengeRepository->findLatestActiveForAccount($account, $challengeType);

        if (!$verificationChallenge instanceof VerificationChallenge) {
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
