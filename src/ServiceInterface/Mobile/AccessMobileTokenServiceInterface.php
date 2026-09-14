<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Mobile;

use App\Accessing\DTO\AccessMobileTokenPairDTO;
use App\Accessing\Entity\AccessEntity;

/**
 * Defines the mobile token service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessMobileTokenServiceInterface
{
    /**
     * Executes the issue operation within the canonical Accessing component workflow.
     */
    public function issue(AccessEntity $user, string $deviceName): AccessMobileTokenPairDTO;

    /**
     * Executes the authenticate operation within the canonical Accessing component workflow.
     */
    public function authenticate(string $accessToken): AccessEntity;

    /**
     * Executes the rotate operation within the canonical Accessing component workflow.
     */
    public function rotate(string $refreshToken): AccessMobileTokenPairDTO;

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(string $accessToken): void;
}
