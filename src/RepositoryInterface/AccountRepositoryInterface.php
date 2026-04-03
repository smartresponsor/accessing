<?php

declare(strict_types=1);

namespace App\RepositoryInterface;

use App\Entity\Account;

interface AccountRepositoryInterface
{
    public function save(Account $account, bool $flush = false): void;

    public function remove(Account $account, bool $flush = false): void;

    public function findOneByEmailAddress(string $emailAddress): ?Account;

    /**
     * @return list<Account>
     */
    public function findRecentAccounts(int $limit = 20): array;
}
