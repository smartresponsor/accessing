<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessAccountSessionEntity;

interface AccountSessionRepositoryInterface
{
    public function save(AccessAccountSessionEntity $accountSession, bool $flush = false): void;

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessAccountSessionEntity;

    /**
     * @return list<AccessAccountSessionEntity>
     */
    public function findActiveForAccount(AccessAccountEntity $account): array;

    public function invalidateOtherActiveSessions(AccessAccountEntity $account, string $keepSessionIdentifier): int;

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int;
}
