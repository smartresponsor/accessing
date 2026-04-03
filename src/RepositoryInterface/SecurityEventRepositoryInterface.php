<?php

declare(strict_types=1);

namespace App\RepositoryInterface;

use App\Entity\Account;
use App\Entity\SecurityEvent;

interface SecurityEventRepositoryInterface
{
    public function save(SecurityEvent $securityEvent, bool $flush = false): void;

    /**
     * @return list<SecurityEvent>
     */
    public function findRecentEvents(int $limit = 50): array;

    /**
     * @return list<SecurityEvent>
     */
    public function findRecentEventsForAccount(Account $account, int $limit = 50): array;
}
