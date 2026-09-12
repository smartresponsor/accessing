<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\OAuth;

use App\Accessing\DTO\AccessExternalIdentityProfileDTO;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the external authentication service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessExternalAuthenticationServiceInterface
{
    /**
     * Executes the resolve operation within the canonical Accessing component workflow.
     */
    public function resolve(AccessExternalIdentityProfileDTO $profile, Request $request): AccessEntity;
}
