<?php

declare(strict_types=1);

namespace App\ServiceInterface\SecurityEvent;

use App\Entity\Account;

interface AccessingSecurityEventViewProviderInterface
{
    /** @return array<int, object> */
    public function listRecentForAccount(Account $account, int $limit = 50): array;
}
