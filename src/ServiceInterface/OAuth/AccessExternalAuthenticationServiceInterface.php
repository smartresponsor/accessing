<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\OAuth;

use App\Accessing\Dto\AccessExternalIdentityProfile;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessExternalAuthenticationServiceInterface
{
    public function resolve(AccessExternalIdentityProfile $profile, Request $request): AccessEntity;
}
