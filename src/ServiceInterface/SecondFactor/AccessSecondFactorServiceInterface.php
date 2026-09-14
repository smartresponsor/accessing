<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecondFactor;

use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;

/**
 * Defines the second factor service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessSecondFactorServiceInterface
{
    /**
     * Executes the begin enrollment operation within the canonical Accessing component workflow.
     */
    public function beginEnrollment(AccessEntity $user): AccessSecondFactorEnrollmentDTO;

    /**
     * Executes the confirm enrollment operation within the canonical Accessing component workflow.
     */
    public function confirmEnrollment(AccessEntity $user, string $code): ?AccessSecondFactorEnrollmentDTO;

    /**
     * Executes the verify challenge operation within the canonical Accessing component workflow.
     */
    public function verifyChallenge(AccessEntity $user, string $code): bool;

    /**
     * Executes the disable second factor operation within the canonical Accessing component workflow.
     */
    public function disableSecondFactor(AccessEntity $user): void;
}
