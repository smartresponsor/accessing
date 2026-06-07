<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;

interface AccessSecurityEventRepositoryInterface
{
    public function save(AccessSecurityEventEntity $securityEvent, bool $flush = false): void;

    /**
     * @return list<AccessSecurityEventEntity>
     */
    public function findRecentEvents(int $limit = 50): array;

    /**
     * @return list<AccessSecurityEventEntity>
     */
    public function findRecentEventsForUser(AccessEntity $user, int $limit = 50): array;
}
