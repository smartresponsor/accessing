<?php

declare(strict_types=1);

namespace App\Service\Verification;

use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\ServiceInterface\Verification\AccessingEmailVerificationServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeManagerInterface;

final class AccessingEmailVerificationService implements AccessingEmailVerificationServiceInterface
{
    public function __construct(
        private readonly AccessingVerificationChallengeManagerInterface $verificationChallengeManager,
        private readonly AccessingSecurityEventRecorderInterface $securityEventRecorder,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function issueChallenge(Account $account): VerificationChallenge
    {
        $challenge = $this->verificationChallengeManager->createEmailChallenge($account);

        $this->securityEventRecorder->record('verification.email.requested', $account, [
            'challengeId' => $challenge->getId(),
            'target' => $challenge->getTarget(),
        ]);

        return $challenge;
    }

    public function confirmChallenge(string $token): ?Account
    {
        $challenge = $this->verificationChallengeManager->findActiveByToken($token, 'email');

        if (!$challenge instanceof VerificationChallenge) {
            return null;
        }

        if ($challenge->getExpiresAt() < new \DateTimeImmutable()) {
            return null;
        }

        $account = $challenge->getAccount();
        if (!$account instanceof Account) {
            return null;
        }

        $challenge->markCompleted();
        $account->markEmailVerified();
        $this->verificationChallengeManager->save($challenge, true);
        $this->accountRepository->save($account, true);

        $this->securityEventRecorder->record('verification.email.completed', $account, [
            'challengeId' => $challenge->getId(),
            'target' => $challenge->getTarget(),
        ]);

        return $account;
    }
}
