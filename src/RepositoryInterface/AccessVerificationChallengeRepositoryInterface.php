<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\ValueObject\AccessVerificationChallengeType;

interface AccessVerificationChallengeRepositoryInterface
{
    public function save(AccessVerificationChallengeEntity $verificationChallenge, bool $flush = false): void;

    public function findLatestActiveForAccount(AccessAccountEntity $account, AccessVerificationChallengeType $challengeType): ?AccessVerificationChallengeEntity;

    /**
     * @return list<AccessVerificationChallengeEntity>
     */
    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array;

    public function cleanupExpiredConsumedBefore(\DateTimeImmutable $before): int;
}
