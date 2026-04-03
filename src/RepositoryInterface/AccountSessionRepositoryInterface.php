<?php

declare(strict_types=1);

namespace App\RepositoryInterface;

use App\Entity\Account;
use App\Entity\AccountSession;

interface AccountSessionRepositoryInterface
{
    public function save(AccountSession $accountSession, bool $flush = false): void;

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccountSession;

    /**
     * @return list<AccountSession>
     */
    public function findActiveForAccount(Account $account): array;

    public function invalidateOtherActiveSessions(Account $account, string $keepSessionIdentifier): int;

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int;
}
