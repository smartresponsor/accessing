<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessUserEntity;

interface AccessCredentialServiceInterface
{
    public function createCredential(AccessUserEntity $user, string $plainPassword): AccessCredentialEntity;

    public function verifyPassword(AccessUserEntity $user, string $plainPassword): bool;

    public function changePassword(AccessUserEntity $user, string $plainPassword): void;
}
