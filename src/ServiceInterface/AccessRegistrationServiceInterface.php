<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface;

use App\Accessing\DTO\AccessRegistrationRequestDTO;
use App\Accessing\Entity\AccessEntity;

/**
 * Defines the registration service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessRegistrationServiceInterface
{
    /**
     * Executes the register operation within the canonical Accessing component workflow.
     */
    public function register(AccessRegistrationRequestDTO $request): AccessEntity;
}
