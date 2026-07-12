<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Verification;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
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
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AccessVerificationChallengeService implements AccessVerificationChallengeServiceInterface
{
    public function __construct(
        private AccessVerificationChallengeRepositoryInterface $verificationChallengeRepository,
        private AccessRepositoryInterface $userRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
        private AccessPhoneVerificationProviderServiceInterface $phoneVerificationProvider,
        private AccessSecurityNotificationServiceInterface $securityNotificationService,
        private RateLimiterFactory $accessingVerificationResendLimiter,
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
    public function issueEmailVerification(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $user,
            AccessVerificationChallengeType::EmailVerification,
            $user->getEmailAddress(),
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        try {
            $this->securityNotificationService->sendEmailVerificationCode(
                $user,
                $issuedChallenge->plainCode,
                $this->accessingVerificationCodeTtlMinutes,
            );
        } catch (\Throwable $exception) {
            $issuedChallenge->challenge->consume();
            $this->verificationChallengeRepository->save($issuedChallenge->challenge, true);
            $this->securityEventService->record(
                AccessSecurityEventType::NotificationDeliveryFailed,
                AccessSecurityEventSeverity::Critical,
                $user,
                $request,
                ['channel' => 'email', 'purpose' => 'verification'],
            );

            throw new AccessNotificationDeliveryException($exception);
        }

        $this->securityEventService->record(
            AccessSecurityEventType::EmailVerificationRequested,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['channel' => 'email', 'purpose' => 'verification'],
        );

        return $issuedChallenge;
    }

    public function resendEmailVerification(AccessEntity $user, ?Request $request = null): ?AccessIssuedChallengeDto
    {
        $limiterKey = sprintf('%s|%s', $user->getId() ?? $user->getEmailAddress(), $request?->getClientIp() ?? 'unknown');

        if (!$this->accessingVerificationResendLimiter->create($limiterKey)->consume()->isAccepted()) {
            $this->securityEventService->record(
                AccessSecurityEventType::RateLimitExceeded,
                AccessSecurityEventSeverity::Warning,
                $user,
                $request,
                ['flow' => 'verification_resend'],
            );

            return null;
        }

        return $this->issueEmailVerification($user, $request);
    }

    /**
     * Issue a phone verification challenge for the supplied phone number.
     *
     * @throws \DateMalformedStringException
     * @throws RandomException
     */
    public function issuePhoneVerification(AccessEntity $user, string $phoneNumber, ?Request $request = null): AccessIssuedChallengeDto
    {
        $user->changePhoneNumber($phoneNumber);

        $issuedChallenge = $this->issueChallenge(
            $user,
            AccessVerificationChallengeType::PhoneVerification,
            $phoneNumber,
            $request,
            $this->accessingVerificationCodeTtlMinutes,
        );

        try {
            $this->phoneVerificationProvider->sendVerificationMessage(
                $phoneNumber,
                sprintf('Accessing phone verification code: %s', $issuedChallenge->plainCode),
            );
        } catch (\Throwable $exception) {
            $issuedChallenge->challenge->consume();
            $this->verificationChallengeRepository->save($issuedChallenge->challenge, true);
            $this->securityEventService->record(
                AccessSecurityEventType::NotificationDeliveryFailed,
                AccessSecurityEventSeverity::Critical,
                $user,
                $request,
                ['channel' => 'phone', 'purpose' => 'verification'],
            );

            throw new AccessNotificationDeliveryException($exception);
        }

        $this->securityEventService->record(
            AccessSecurityEventType::PhoneVerificationRequested,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['channel' => 'phone', 'purpose' => 'verification'],
        );

        $this->userRepository->save($user, true);

        return $issuedChallenge;
    }

    /**
     * Issue a password recovery challenge for the user.
     *
     * @throws \DateMalformedStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function issuePasswordRecovery(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDto
    {
        $issuedChallenge = $this->issueChallenge(
            $user,
            AccessVerificationChallengeType::PasswordRecovery,
            $user->getEmailAddress(),
            $request,
            $this->accessingRecoveryCodeTtlMinutes,
        );

        try {
            $this->securityNotificationService->sendPasswordRecoveryCode(
                $user,
                $issuedChallenge->plainCode,
                $this->accessingRecoveryCodeTtlMinutes,
            );
        } catch (\Throwable $exception) {
            $issuedChallenge->challenge->consume();
            $this->verificationChallengeRepository->save($issuedChallenge->challenge, true);
            $this->securityEventService->record(
                AccessSecurityEventType::NotificationDeliveryFailed,
                AccessSecurityEventSeverity::Critical,
                $user,
                $request,
                ['channel' => 'email', 'purpose' => 'recovery'],
            );

            throw new AccessNotificationDeliveryException($exception);
        }

        $this->securityEventService->record(
            AccessSecurityEventType::RecoveryRequested,
            AccessSecurityEventSeverity::Warning,
            $user,
            $request,
        );

        return $issuedChallenge;
    }

    /**
     * Complete email verification when a valid challenge code is provided.
     */
    public function completeEmailVerification(AccessEntity $user, string $code): bool
    {
        if (!$this->consumeChallenge($user, AccessVerificationChallengeType::EmailVerification, $code)) {
            return false;
        }

        $user->markEmailVerified();
        $this->userRepository->save($user, true);

        $this->securityEventService->record(AccessSecurityEventType::EmailVerified, AccessSecurityEventSeverity::Info, $user);

        return true;
    }

    /**
     * Complete phone verification when a valid challenge code is provided.
     */
    public function completePhoneVerification(AccessEntity $user, string $code): bool
    {
        if (!$this->consumeChallenge($user, AccessVerificationChallengeType::PhoneVerification, $code)) {
            return false;
        }

        $user->markPhoneVerified();
        $this->userRepository->save($user, true);

        $this->securityEventService->record(AccessSecurityEventType::PhoneVerified, AccessSecurityEventSeverity::Info, $user);

        return true;
    }

    /**
     * Consume password recovery challenge with a one-time code.
     */
    public function consumePasswordRecovery(AccessEntity $user, string $code): bool
    {
        return $this->consumeChallenge($user, AccessVerificationChallengeType::PasswordRecovery, $code);
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
        AccessEntity $user,
        AccessVerificationChallengeType $challengeType,
        string $destination,
        ?Request $request,
        int $ttlMinutes,
    ): AccessIssuedChallengeDto {
        $plainCode = (string) random_int(100000, 999999);

        $verificationChallenge = new AccessVerificationChallengeEntity(
            $user,
            $challengeType,
            $destination,
            $this->hashCode($plainCode),
            new \DateTimeImmutable(sprintf('+%d minutes', $ttlMinutes)),
            $request?->getClientIp(),
        );

        $user->addVerificationChallenge($verificationChallenge);
        $this->verificationChallengeRepository->save($verificationChallenge, true);

        return new AccessIssuedChallengeDto($verificationChallenge, $plainCode);
    }

    private function consumeChallenge(AccessEntity $user, AccessVerificationChallengeType $challengeType, string $code): bool
    {
        $verificationChallenge = $this->verificationChallengeRepository->findLatestActiveForUser($user, $challengeType);

        if (!$verificationChallenge instanceof AccessVerificationChallengeEntity) {
            return false;
        }

        $verificationChallenge->registerAttempt();

        if (!hash_equals($verificationChallenge->getCodeHash(), $this->hashCode(trim($code)))) {
            if ($verificationChallenge->hasReachedAttemptLimit()) {
                $verificationChallenge->consume();
                $this->securityEventService->record(
                    AccessSecurityEventType::VerificationAttemptLimitReached,
                    AccessSecurityEventSeverity::Warning,
                    $user,
                    context: ['challengeType' => $challengeType->value],
                );
            }

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
