<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;

interface AccessRecoveryCodeRepositoryInterface
{
    public function save(AccessRecoveryCodeEntity $recoveryCode, bool $flush = false): void;

    /**
     * @return list<AccessRecoveryCodeEntity>
     */
    public function findActiveForUser(AccessEntity $user): array;

    public function cleanupConsumedBefore(\DateTimeImmutable $before): int;
}
