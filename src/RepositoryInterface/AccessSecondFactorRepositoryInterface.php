<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;

/**
 * Defines the second factor repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessSecondFactorRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessSecondFactorEntity $secondFactor, bool $flush = false): void;

    /**
     * Executes the find enabled for user operation within the canonical Accessing component workflow.
     */
    public function findEnabledForUser(AccessEntity $user): ?AccessSecondFactorEntity;
}
