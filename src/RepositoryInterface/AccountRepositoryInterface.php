<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessAccountEntity;

interface AccountRepositoryInterface
{
    public function save(AccessAccountEntity $account, bool $flush = false): void;

    public function remove(AccessAccountEntity $account, bool $flush = false): void;

    public function findById(int $id): ?AccessAccountEntity;

    public function findOneByEmailAddress(string $emailAddress): ?AccessAccountEntity;

    /**
     * @return list<AccessAccountEntity>
     */
    public function findRecentAccounts(int $limit = 20): array;
}
