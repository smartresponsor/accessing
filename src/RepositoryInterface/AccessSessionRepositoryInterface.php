<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;

interface AccessSessionRepositoryInterface
{
    public function save(AccessSessionEntity $userSession, bool $flush = false): void;

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessSessionEntity;

    /**
     * @return list<AccessSessionEntity>
     */
    public function findActiveForUser(AccessEntity $user): array;

    public function invalidateOtherActiveSessions(AccessEntity $user, string $keepSessionIdentifier): int;

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int;
}
