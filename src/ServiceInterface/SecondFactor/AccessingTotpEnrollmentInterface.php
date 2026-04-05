<?php

declare(strict_types=1);

namespace App\ServiceInterface\SecondFactor;

use App\Entity\Account;

interface AccessingTotpEnrollmentInterface
{
    public function prepare(Account $account): Account;

    public function enable(Account $account): void;

    public function disable(Account $account): void;

    public function buildProvisioningUri(Account $account, string $issuer = 'Accessing'): string;
}
