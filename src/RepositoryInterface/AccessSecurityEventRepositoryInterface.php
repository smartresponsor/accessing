<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessUserEntity;

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
    public function findRecentEventsForUser(AccessUserEntity $user, int $limit = 50): array;
}
