<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecondFactor;

use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Random\RandomException;

final readonly class AccessSecondFactorService implements AccessSecondFactorServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessSecurityEventServiceInterface $securityEventService,
        private string $appSecret,
    ) {
    }

    public function beginEnrollment(AccessEntity $user): AccessSecondFactorEnrollmentDto
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity) {
            $totp = TOTP::create();
            $label = $this->nonEmptyLabel($user->getEmailAddress());
            $totp->setLabel($label);
            $totp->setIssuer('Accessing');

            $secondFactor = new AccessSecondFactorEntity($user, $totp->getSecret(), $user->getEmailAddress());
            $user->setSecondFactor($secondFactor);
            $this->entityManager->persist($secondFactor);
            $this->entityManager->flush();

            return new AccessSecondFactorEnrollmentDto($totp->getSecret(), $totp->getProvisioningUri());
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret);
        $label = $this->nonEmptyLabel($user->getEmailAddress());
        $totp->setLabel($label);
        $totp->setIssuer('Accessing');

        return new AccessSecondFactorEnrollmentDto($secondFactor->getSecret(), $totp->getProvisioningUri());
    }

    /**
     * @throws RandomException
     */
    public function confirmEnrollment(AccessEntity $user, string $code): ?AccessSecondFactorEnrollmentDto
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity) {
            return null;
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret);
        $normalizedVerificationCode = trim($code);

        if ('' === $normalizedVerificationCode || !$totp->verify($normalizedVerificationCode)) {
            return null;
        }

        $secondFactor->confirm();

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            $this->entityManager->remove($recoveryCode);
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

        $this->entityManager->flush();

        $this->securityEventService->record(
            AccessSecurityEventType::SecondFactorEnrolled,
            AccessSecurityEventSeverity::Info,
            $user,
        );

        $totp->setLabel($this->nonEmptyLabel($user->getEmailAddress()));
        $totp->setIssuer('Accessing');

        return new AccessSecondFactorEnrollmentDto($secondFactor->getSecret(), $totp->getProvisioningUri(), $plainRecoveryCodes);
    }

    public function verifyChallenge(AccessEntity $user, string $code): bool
    {
        $secondFactor = $user->getSecondFactor();

        if (!$secondFactor instanceof AccessSecondFactorEntity || !$secondFactor->isEnabled()) {
            return false;
        }

        $normalizedCode = strtoupper(trim(str_replace([' ', '-'], '', $code)));
        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret);

        if ('' !== $normalizedCode && $totp->verify($normalizedCode)) {
            $secondFactor->markUsed();
            $this->entityManager->flush();

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
            $this->entityManager->flush();

            $this->securityEventService->record(
                AccessSecurityEventType::RecoveryCodeUsed,
                AccessSecurityEventSeverity::Warning,
                $user,
            );

            return true;
        }

        return false;
    }

    public function disableSecondFactor(AccessEntity $user): void
    {
        $secondFactor = $user->getSecondFactor();

        if ($secondFactor instanceof AccessSecondFactorEntity) {
            $secondFactor->revoke();
        }

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            $this->entityManager->remove($recoveryCode);
        }

        $this->entityManager->flush();

        $this->securityEventService->record(
            AccessSecurityEventType::SecondFactorRevoked,
            AccessSecurityEventSeverity::Warning,
            $user,
        );
    }

    private function hashRecoveryCode(string $code): string
    {
        return hash_hmac('sha256', $code, $this->appSecret);
    }

    /** @return non-empty-string */
    private function nonEmptySecret(string $secret): string
    {
        $normalizedSecret = trim($secret);

        return '' !== $normalizedSecret ? $normalizedSecret : 'ACCESSING-DEFAULT-SECRET';
    }

    /** @return non-empty-string */
    private function nonEmptyLabel(string $label): string
    {
        $normalizedLabel = trim($label);

        return '' !== $normalizedLabel ? $normalizedLabel : 'accessing';
    }
}
