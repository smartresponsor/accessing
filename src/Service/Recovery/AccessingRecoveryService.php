<?php

declare(strict_types=1);

namespace App\Service\Recovery;

use App\Dto\AccessingIssuedChallengeDto;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\ServiceInterface\Recovery\AccessingRecoveryServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\ValueObject\EmailAddress;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessingRecoveryService implements AccessingRecoveryServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessingCredentialServiceInterface $credentialService,
        private AccessingSecurityEventServiceInterface $securityEventService,
    ) {}

    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessingIssuedChallengeDto
    {
        $normalizedEmailAddress = new EmailAddress($emailAddress);
        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if ($account === null) {
            return null;
        }

        return $this->verificationChallengeService->issuePasswordRecovery($account, $request);
    }

    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool
    {
        $normalizedEmailAddress = new EmailAddress($emailAddress);
        $account = $this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if ($account === null) {
            return false;
        }

        if (!$this->verificationChallengeService->consumePasswordRecovery($account, $code)) {
            return false;
        }

        $this->credentialService->changePassword($account, $newPassword);
        $account->unlock();
        $this->accountRepository->save($account, true);

        $this->securityEventService->record(
            SecurityEventType::RecoveryCompleted,
            SecurityEventSeverity::Warning,
            $account,
            null,
        );

        return true;
    }
}
