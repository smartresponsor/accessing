<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Mobile;

use App\Accessing\DTO\AccessMobilePendingTokenDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;

/**
 * Defines the mobile pending auth service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessMobilePendingAuthServiceInterface
{
    /**
     * Executes the issue operation within the canonical Accessing component workflow.
     */
    public function issue(AccessEntity $user, AccessMobilePendingPurpose $purpose, string $deviceName): AccessMobilePendingTokenDTO;

    /**
     * Executes the resolve operation within the canonical Accessing component workflow.
     */
    public function resolve(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity;

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity;
}
