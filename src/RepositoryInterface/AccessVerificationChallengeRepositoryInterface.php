<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\ValueObject\AccessVerificationChallengeType;

/**
 * Defines the verification challenge repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessVerificationChallengeRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessVerificationChallengeEntity $verificationChallenge, bool $flush = false): void;

    /**
     * Executes the find latest active for user operation within the canonical Accessing component workflow.
     */
    public function findLatestActiveForUser(AccessEntity $user, AccessVerificationChallengeType $challengeType): ?AccessVerificationChallengeEntity;

    /**
     * @return list<AccessVerificationChallengeEntity>
     */
    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array;

    /**
     * Executes the cleanup expired consumed before operation within the canonical Accessing component workflow.
     */
    public function cleanupExpiredConsumedBefore(\DateTimeImmutable $before): int;
}
