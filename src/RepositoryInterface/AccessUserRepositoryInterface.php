<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessUserEntity;

interface AccessUserRepositoryInterface
{
    public function save(AccessUserEntity $user, bool $flush = false): void;

    public function remove(AccessUserEntity $user, bool $flush = false): void;

    public function findById(int $id): ?AccessUserEntity;

    public function findOneByEmailAddress(string $emailAddress): ?AccessUserEntity;

    /**
     * @return list<AccessUserEntity>
     */
    public function findRecentUsers(int $limit = 20): array;
}
