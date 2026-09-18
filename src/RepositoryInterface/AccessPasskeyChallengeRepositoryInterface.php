<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessPasskeyChallengeEntity;

/**
 * Defines the passkey challenge repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyChallengeRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessPasskeyChallengeEntity $challenge, bool $flush = false): void;

    /**
     * Executes the find one by challenge hash operation within the canonical Accessing component workflow.
     */
    public function findOneByChallengeHash(string $challengeHash): ?AccessPasskeyChallengeEntity;
}
