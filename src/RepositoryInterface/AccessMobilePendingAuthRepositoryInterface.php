<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessMobilePendingAuthEntity;

interface AccessMobilePendingAuthRepositoryInterface
{
    public function save(AccessMobilePendingAuthEntity $pendingAuth, bool $flush = false): void;

    public function findOneByTokenHash(string $tokenHash): ?AccessMobilePendingAuthEntity;
}
