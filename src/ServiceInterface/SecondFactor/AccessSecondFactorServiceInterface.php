<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecondFactor;

use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessUserEntity;

interface AccessSecondFactorServiceInterface
{
    public function beginEnrollment(AccessUserEntity $user): AccessSecondFactorEnrollmentDto;

    public function confirmEnrollment(AccessUserEntity $user, string $code): ?AccessSecondFactorEnrollmentDto;

    public function verifyChallenge(AccessUserEntity $user, string $code): bool;

    public function disableSecondFactor(AccessUserEntity $user): void;
}
