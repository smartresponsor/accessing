<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Recovery;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessRecoveryService implements AccessRecoveryServiceInterface
{
    public function __construct(
        private AccessAccountRepositoryInterface $accountRepository,
        private AccessVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessCredentialServiceInterface $credentialService,
        private AccessSecurityEventServiceInterface $securityEventService,
    ) {
    }

    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessIssuedChallengeDto
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (null === $account) {
            return null;
        }

        return $this->verificationChallengeService->issuePasswordRecovery($account, $request);
    }

    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (null === $account) {
            return false;
        }

        if (!$this->verificationChallengeService->consumePasswordRecovery($account, $code)) {
            return false;
        }

        $this->credentialService->changePassword($account, $newPassword);
        $account->unlock();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(
            AccessSecurityEventType::RecoveryCompleted,
            AccessSecurityEventSeverity::Warning,
            $account,
        );

        return true;
    }
}
