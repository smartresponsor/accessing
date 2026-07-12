<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Mobile;

use App\Accessing\Dto\AccessMobileTokenPair;
use App\Accessing\Entity\AccessEntity;

interface AccessMobileTokenServiceInterface
{
    public function issue(AccessEntity $user, string $deviceName): AccessMobileTokenPair;

    public function authenticate(string $accessToken): AccessEntity;

    public function rotate(string $refreshToken): AccessMobileTokenPair;

    public function revoke(string $accessToken): void;
}
