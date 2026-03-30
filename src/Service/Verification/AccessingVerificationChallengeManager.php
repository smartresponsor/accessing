<?php

declare(strict_types=1);

namespace App\Service\Verification;

use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\ServiceInterface\Verification\AccessingVerificationChallengeManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AccessingVerificationChallengeManager implements AccessingVerificationChallengeManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createEmailChallenge(Account $account): VerificationChallenge
    {
        return $this->createChallenge($account, 'email', $account->getEmail());
    }

    public function createPhoneChallenge(Account $account, string $phoneNumber): VerificationChallenge
    {
        return $this->createChallenge($account, 'phone', $phoneNumber);
    }

    private function createChallenge(Account $account, string $channelType, string $target): VerificationChallenge
    {
        $challenge = (new VerificationChallenge())
            ->setAccount($account)
            ->setChannelType($channelType)
            ->setTarget($target)
            ->setToken($this->generateToken());

        $this->entityManager->persist($challenge);
        $this->entityManager->flush();

        return $challenge;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
