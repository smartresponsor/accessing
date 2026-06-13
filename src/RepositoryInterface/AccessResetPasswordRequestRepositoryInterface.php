<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessResetPasswordRequestEntity;

interface AccessResetPasswordRequestRepositoryInterface
{
    public function save(AccessResetPasswordRequestEntity $resetPasswordRequest, bool $flush = false): void;
}
