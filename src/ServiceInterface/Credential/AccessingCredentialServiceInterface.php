<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Credential;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessCredentialEntity;

interface AccessingCredentialServiceInterface
{
    public function createCredential(AccessAccountEntity $account, string $plainPassword): AccessCredentialEntity;

    public function verifyPassword(AccessAccountEntity $account, string $plainPassword): bool;

    public function changePassword(AccessAccountEntity $account, string $plainPassword): void;
}
