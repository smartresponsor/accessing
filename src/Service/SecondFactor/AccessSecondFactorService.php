<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecondFactor;

use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\RepositoryInterface\AccessPersistenceRepositoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use Random\RandomException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Defines the second factor service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSecondFactorService implements AccessSecondFactorServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessPersistenceRepositoryInterface $persistenceRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
        private RateLimiterFactory $accessingSecondFactorLimiter,
        private ClockInterface $clock,
        private string $appSecret,
    ) {
    }

    /**
     * Executes the begin enrollment operation within the canonical Accessing component workflow.
     */
    public function beginEnrollment(AccessEntity $user): AccessSecondFactorEnrollmentDTO
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity) {
            $totp = TOTP::create(clock: $this->clock);
            $label = $this->nonEmptyLabel($user->getEmailAddress());
            $totp->setLabel($label);
            $totp->setIssuer('Accessing');

            $secondFactor = new AccessSecondFactorEntity($user, $totp->getSecret(), $user->getEmailAddress());
            $user->setSecondFactor($secondFactor);
            $this->persistenceRepository->persist($secondFactor);
            $this->persistenceRepository->flush();

            return new AccessSecondFactorEnrollmentDTO($totp->getSecret(), $totp->getProvisioningUri());
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret, clock: $this->clock);
        $label = $this->nonEmptyLabel($user->getEmailAddress());
        $totp->setLabel($label);
        $totp->setIssuer('Accessing');

        return new AccessSecondFactorEnrollmentDTO($secondFactor->getSecret(), $totp->getProvisioningUri());
    }

    /**
     * @throws RandomException
     */
    public function confirmEnrollment(AccessEntity $user, string $code): ?AccessSecondFactorEnrollmentDTO
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity) {
            return null;
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret, clock: $this->clock);
        $normalizedVerificationCode = trim($code);

        if ('' === $normalizedVerificationCode || !$totp->verify($normalizedVerificationCode)) {
            return null;
        }

        $secondFactor->confirm();

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            $this->persistenceRepository->remove($recoveryCode);
        }

        $plainRecoveryCodes = [];

        for ($index = 0; $index < 8; ++$index) {
            $plainRecoveryCode = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $plainRecoveryCodes[] = $plainRecoveryCode;
            $user->addRecoveryCode(new AccessRecoveryCodeEntity(
                $user,
                $this->hashRecoveryCode($plainRecoveryCode),
                substr($plainRecoveryCode, -4),
            ));
        }

        $this->persistenceRepository->flush();

        $this->securityEventService->record(
            AccessSecurityEventType::SecondFactorEnrolled,
            AccessSecurityEventSeverity::Info,
            $user,
        );

        $totp->setLabel($this->nonEmptyLabel($user->getEmailAddress()));
        $totp->setIssuer('Accessing');

        return new AccessSecondFactorEnrollmentDTO($secondFactor->getSecret(), $totp->getProvisioningUri(), $plainRecoveryCodes);
    }

    /**
     * Executes the verify challenge operation within the canonical Accessing component workflow.
     */
    public function verifyChallenge(AccessEntity $user, string $code): bool
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity || !$secondFactor->isEnabled()) {
            return false;
        }

        $limiterKey = null !== $user->getId() ? (string) $user->getId() : $user->getEmailAddress();

        if (!$this->accessingSecondFactorLimiter->create($limiterKey)->consume()->isAccepted()) {
            $this->securityEventService->record(
                AccessSecurityEventType::RateLimitExceeded,
                AccessSecurityEventSeverity::Warning,
                $user,
                context: ['flow' => 'second_factor'],
            );

            return false;
        }

        $normalizedCode = strtoupper(trim(str_replace([' ', '-'], '', $code)));
        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret, clock: $this->clock);

        if ('' !== $normalizedCode && $totp->verify($normalizedCode)) {
            $secondFactor->markUsed();
            $this->persistenceRepository->flush();

            return true;
        }

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            if ($recoveryCode->isUsed()) {
                continue;
            }

            if (!hash_equals($recoveryCode->getCodeHash(), $this->hashRecoveryCode($normalizedCode))) {
                continue;
            }

            $recoveryCode->markUsed();
            $this->persistenceRepository->flush();

            $this->securityEventService->record(
                AccessSecurityEventType::RecoveryCodeUsed,
                AccessSecurityEventSeverity::Warning,
                $user,
            );

            return true;
        }

        return false;
    }

    /**
     * Executes the disable second factor operation within the canonical Accessing component workflow.
     */
    public function disableSecondFactor(AccessEntity $user): void
    {
        $secondFactor = $user->getSecondFactor();

        if ($secondFactor instanceof AccessSecondFactorEntity) {
            $secondFactor->revoke();
        }

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            $this->persistenceRepository->remove($recoveryCode);
        }

        $this->persistenceRepository->flush();

        $this->securityEventService->record(
            AccessSecurityEventType::SecondFactorRevoked,
            AccessSecurityEventSeverity::Warning,
            $user,
        );
    }

    /**
     * Executes the hash recovery code operation within the canonical Accessing component workflow.
     */
    private function hashRecoveryCode(string $code): string
    {
        return hash_hmac('sha256', $code, $this->appSecret);
    }

    /** @return non-empty-string */
    private function nonEmptySecret(string $secret): string
    {
        $normalizedSecret = trim($secret);

        if ('' === $normalizedSecret) {
            throw new \LogicException('Second-factor secret must not be empty.');
        }

        return $normalizedSecret;
    }

    /** @return non-empty-string */
    private function nonEmptyLabel(string $label): string
    {
        $normalizedLabel = trim($label);

        return '' !== $normalizedLabel ? $normalizedLabel : 'accessing';
    }
}
