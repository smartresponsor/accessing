<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessResetPasswordRequestEntity;

/**
 * Defines the reset password request repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessResetPasswordRequestRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessResetPasswordRequestEntity $resetPasswordRequest, bool $flush = false): void;
}
