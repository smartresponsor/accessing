<?php

declare(strict_types=1);

namespace App\Service\SecondFactor;

use App\Dto\AccessingSecondFactorEnrollmentDto;
use App\Entity\Account;
use App\Entity\RecoveryCode;
use App\Entity\SecondFactor;
use App\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;

final readonly class AccessingSecondFactorService implements AccessingSecondFactorServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessingSecurityEventServiceInterface $securityEventService,
        private string $appSecret,
    ) {}

    public function beginEnrollment(Account $account): AccessingSecondFactorEnrollmentDto
    {
        $secondFactor = $account->getSecondFactor();

        if (!$secondFactor instanceof SecondFactor) {
            $totp = TOTP::create();
            $totp->setLabel($account->getEmailAddress());
            $totp->setIssuer('Accessing');

            $secondFactor = new SecondFactor($account, $totp->getSecret(), $account->getEmailAddress());
            $account->setSecondFactor($secondFactor);
            $this->entityManager->persist($secondFactor);
            $this->entityManager->flush();

            return new AccessingSecondFactorEnrollmentDto($totp->getSecret(), $totp->getProvisioningUri());
        }

        $totp = TOTP::create($secondFactor->getSecret());
        $totp->setLabel($account->getEmailAddress());
        $totp->setIssuer('Accessing');

        return new AccessingSecondFactorEnrollmentDto($secondFactor->getSecret(), $totp->getProvisioningUri());
    }

    public function confirmEnrollment(Account $account, string $code): ?AccessingSecondFactorEnrollmentDto
    {
        $secondFactor = $account->getSecondFactor();

        if (!$secondFactor instanceof SecondFactor) {
            return null;
        }

        $totp = TOTP::create($secondFactor->getSecret());

        if (!$totp->verify(trim($code))) {
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

        $totp->setLabel($account->getEmailAddress());
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
        $totp = TOTP::create($secondFactor->getSecret());

        if ($totp->verify($normalizedCode)) {
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
}
