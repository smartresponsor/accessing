<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\SecurityEvent;

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
