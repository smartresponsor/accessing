<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\SecondFactor;

use App\Accessing\Dto\AccessingSecondFactorEnrollmentDto;
use App\Accessing\Entity\Account;
use App\Accessing\Entity\RecoveryCode;
use App\Accessing\Entity\SecondFactor;
use App\Accessing\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\Accessing\ValueObject\SecurityEventSeverity;
use App\Accessing\ValueObject\SecurityEventType;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Random\RandomException;

final readonly class AccessingSecondFactorService implements AccessingSecondFactorServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private string $appSecret,
    ) {
    }

    public function beginEnrollment(Account $account): AccessingSecondFactorEnrollmentDto
    {
        $secondFactor = $account->getSecondFactor();

        if (!$secondFactor instanceof SecondFactor) {
            $totp = TOTP::create();
            $label = $this->nonEmptyLabel($account->getEmailAddress());
            $totp->setLabel($label);
            $totp->setIssuer('Accessing');

            $secondFactor = new SecondFactor($account, $totp->getSecret(), $account->getEmailAddress());
            $account->setSecondFactor($secondFactor);
            $this->entityManager->persist($secondFactor);
            $this->entityManager->flush();

            return new AccessingSecondFactorEnrollmentDto($totp->getSecret(), $totp->getProvisioningUri());
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret);
        $label = $this->nonEmptyLabel($account->getEmailAddress());
        $totp->setLabel($label);
        $totp->setIssuer('Accessing');

        return new AccessingSecondFactorEnrollmentDto($secondFactor->getSecret(), $totp->getProvisioningUri());
    }

    /**
     * @throws RandomException
     */
    public function confirmEnrollment(Account $account, string $code): ?AccessingSecondFactorEnrollmentDto
    {
        $secondFactor = $account->getSecondFactor();

        if (!$secondFactor instanceof SecondFactor) {
            return null;
        }

        $secret = $this->nonEmptySecret($secondFactor->getSecret());
        $totp = TOTP::create($secret);
        $normalizedVerificationCode = trim($code);

        if ('' === $normalizedVerificationCode || !$totp->verify($normalizedVerificationCode)) {
            return null;
        }

        $secondFactor->confirm();

        foreach ($account->getRecoveryCodes() as $recoveryCode) {
            $this->entityManager->remove($recoveryCode);
        }

        $plainRecoveryCodes = [];

        for ($index = 0; $index < 8; ++$index) {
            $plainRecoveryCode = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $plainRecoveryCodes[] = $plainRecoveryCode;
            $account->addRecoveryCode(new RecoveryCode(
                $account,
                $this->hashRecoveryCode($plainRecoveryCode),
                substr($plainRecoveryCode, -4),
            ));
        }

        $this->entityManager->flush();

        $this->securityEventService->record(
            SecurityEventType::SecondFactorEnrolled,
            SecurityEventSeverity::Info,
            $account,
        );

        $totp->setLabel($this->nonEmptyLabel($account->getEmailAddress()));
        $totp->setIssuer('Accessing');

        return new AccessingSecondFactorEnrollmentDto($secondFactor->getSecret(), $totp->getProvisioningUri(), $plainRecoveryCodes);
    }

    public function verifyChallenge(Account $account, string $code): bool
    {
        $secondFactor = $account->getSecondFactor();

        if (!$secondFactor instanceof SecondFactor || !$secondFactor->isEnabled()) {
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

        foreach ($account->getRecoveryCodes() as $recoveryCode) {
            if ($recoveryCode->isUsed()) {
                continue;
            }

            if (!hash_equals($recoveryCode->getCodeHash(), $this->hashRecoveryCode($normalizedCode))) {
                continue;
            }

            $recoveryCode->markUsed();
            $this->entityManager->flush();

            $this->securityEventService->record(
                SecurityEventType::RecoveryCodeUsed,
                SecurityEventSeverity::Warning,
                $account,
            );

            return true;
        }

        return false;
    }

    public function disableSecondFactor(Account $account): void
    {
        $secondFactor = $account->getSecondFactor();

        if ($secondFactor instanceof SecondFactor) {
            $secondFactor->revoke();
        }

        foreach ($account->getRecoveryCodes() as $recoveryCode) {
            $this->entityManager->remove($recoveryCode);
        }

        $this->entityManager->flush();

        $this->securityEventService->record(
            SecurityEventType::SecondFactorRevoked,
            SecurityEventSeverity::Warning,
            $account,
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
