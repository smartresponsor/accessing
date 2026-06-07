<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;

interface AccessRepositoryInterface
{
    public function save(AccessEntity $user, bool $flush = false): void;

    public function remove(AccessEntity $user, bool $flush = false): void;

    public function findById(int $id): ?AccessEntity;

    public function findOneByEmailAddress(string $emailAddress): ?AccessEntity;

    /**
     * @return list<AccessEntity>
     */
    public function findRecentUsers(int $limit = 20): array;
}
