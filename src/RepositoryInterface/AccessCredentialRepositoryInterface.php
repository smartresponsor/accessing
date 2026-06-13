<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;

interface AccessCredentialRepositoryInterface
{
    public function save(AccessCredentialEntity $credential, bool $flush = false): void;

    public function findOneForUser(AccessEntity $user): ?AccessCredentialEntity;
}
