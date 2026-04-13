<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\ServiceInterface\Credential;

use App\Entity\Account;
use App\Entity\Credential;

interface AccessingCredentialServiceInterface
{
    public function createCredential(Account $account, string $plainPassword): Credential;

    public function verifyPassword(Account $account, string $plainPassword): bool;

    public function changePassword(Account $account, string $plainPassword): void;
}
