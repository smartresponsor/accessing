<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Entity\AccessUserSessionEntity;

interface AccessUserSessionRepositoryInterface
{
    public function save(AccessUserSessionEntity $userSession, bool $flush = false): void;

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessUserSessionEntity;

    /**
     * @return list<AccessUserSessionEntity>
     */
    public function findActiveForUser(AccessUserEntity $user): array;

    public function invalidateOtherActiveSessions(AccessUserEntity $user, string $keepSessionIdentifier): int;

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int;
}
