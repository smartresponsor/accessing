<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Mobile;

use App\Accessing\Dto\AccessMobilePendingToken;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;

interface AccessMobilePendingAuthServiceInterface
{
    public function issue(AccessEntity $user, AccessMobilePendingPurpose $purpose, string $deviceName): AccessMobilePendingToken;

    public function resolve(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity;

    public function consume(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity;
}
