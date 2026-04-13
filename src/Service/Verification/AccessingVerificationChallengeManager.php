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

    public function findActiveByToken(string $token, string $channelType): ?VerificationChallenge
    {
        /** @var VerificationChallenge|null $challenge */
        $challenge = $this->entityManager->createQuery(
            'SELECT challenge FROM App\Entity\VerificationChallenge challenge WHERE challenge.token = :token AND challenge.channelType = :channelType AND challenge.completed = false'
        )
            ->setParameter('token', trim($token))
            ->setParameter('channelType', $channelType)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $challenge instanceof VerificationChallenge ? $challenge : null;
    }

    public function save(VerificationChallenge $challenge, bool $flush = false): void
    {
        $this->entityManager->persist($challenge);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function createChallenge(Account $account, string $channelType, string $target): VerificationChallenge
    {
        $challenge = (new VerificationChallenge())
            ->setAccount($account)
            ->setChannelType($channelType)
            ->setTarget($target)
            ->setToken($this->generateToken());

        $this->save($challenge, true);

        return $challenge;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
