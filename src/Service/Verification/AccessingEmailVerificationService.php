<?php

declare(strict_types=1);

namespace App\Service\Verification;

use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\ServiceInterface\Verification\AccessingEmailVerificationServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AccessingEmailVerificationService implements AccessingEmailVerificationServiceInterface
{
    public function __construct(
        private readonly AccessingVerificationChallengeManagerInterface $verificationChallengeManager,
        private readonly AccessingSecurityEventRecorderInterface $securityEventRecorder,
        private readonly EntityManagerInterface $entityManager,
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
        /** @var VerificationChallenge|null $challenge */
        $challenge = $this->entityManager->createQuery(
            'SELECT challenge FROM App\Entity\VerificationChallenge challenge JOIN challenge.account account WHERE challenge.token = :token AND challenge.channelType = :channelType AND challenge.completed = false'
        )
            ->setParameter('token', trim($token))
            ->setParameter('channelType', 'email')
            ->setMaxResults(1)
            ->getOneOrNullResult();

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
        $this->entityManager->flush();

        $this->securityEventRecorder->record('verification.email.completed', $account, [
            'challengeId' => $challenge->getId(),
            'target' => $challenge->getTarget(),
        ]);

        return $account;
    }
}
