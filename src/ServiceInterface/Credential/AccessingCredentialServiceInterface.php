<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Credential;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\Credential;

interface AccessingCredentialServiceInterface
{
    public function createCredential(Account $account, string $plainPassword): Credential;

    public function verifyPassword(Account $account, string $plainPassword): bool;

    public function changePassword(Account $account, string $plainPassword): void;
}
