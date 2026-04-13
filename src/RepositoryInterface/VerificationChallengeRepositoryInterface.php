<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\RepositoryInterface;

use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\ValueObject\VerificationChallengeType;

interface VerificationChallengeRepositoryInterface
{
    public function save(VerificationChallenge $verificationChallenge, bool $flush = false): void;

    public function findLatestActiveForAccount(Account $account, VerificationChallengeType $challengeType): ?VerificationChallenge;

    /**
     * @return list<VerificationChallenge>
     */
    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array;

    public function cleanupExpiredConsumedBefore(\DateTimeImmutable $before): int;
}
