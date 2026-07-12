<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessPasskeyChallengeEntity;

interface AccessPasskeyChallengeRepositoryInterface
{
    public function save(AccessPasskeyChallengeEntity $challenge, bool $flush = false): void;

    public function findOneByChallengeHash(string $challengeHash): ?AccessPasskeyChallengeEntity;
}
