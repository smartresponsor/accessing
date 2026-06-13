<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;

interface AccessSecondFactorRepositoryInterface
{
    public function save(AccessSecondFactorEntity $secondFactor, bool $flush = false): void;

    public function findEnabledForUser(AccessEntity $user): ?AccessSecondFactorEntity;
}
