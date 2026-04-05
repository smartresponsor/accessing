<?php

declare(strict_types=1);

namespace App\ServiceInterface\AccountSession;

use App\Entity\Account;
use App\Entity\AccountSession;

interface AccessingAccountSessionManagerInterface
{
    public function create(Account $account, ?string $sessionIdentifier = null): AccountSession;

    public function revoke(AccountSession $accountSession): void;
}
