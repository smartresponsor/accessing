<?php

declare(strict_types=1);

namespace App\ServiceInterface\AccountSession;

use App\Entity\Account;

interface AccessingAccountSessionViewProviderInterface
{
    /** @return array<int, object> */
    public function listRecentForAccount(Account $account, int $limit = 50): array;
}
