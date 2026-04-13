<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\RepositoryInterface;

use App\Entity\Account;

interface AccountRepositoryInterface
{
    public function save(Account $account, bool $flush = false): void;

    public function remove(Account $account, bool $flush = false): void;

    public function findById(int $id): ?Account;

    public function findOneByEmailAddress(string $emailAddress): ?Account;

    /**
     * @return list<Account>
     */
    public function findRecentAccounts(int $limit = 20): array;
}
