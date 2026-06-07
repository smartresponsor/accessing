<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;

interface AccessCredentialServiceInterface
{
    public function createCredential(AccessEntity $user, string $plainPassword): AccessCredentialEntity;

    public function verifyPassword(AccessEntity $user, string $plainPassword): bool;

    public function changePassword(AccessEntity $user, string $plainPassword): void;
}
