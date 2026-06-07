<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecondFactor;

use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessEntity;

interface AccessSecondFactorServiceInterface
{
    public function beginEnrollment(AccessEntity $user): AccessSecondFactorEnrollmentDto;

    public function confirmEnrollment(AccessEntity $user, string $code): ?AccessSecondFactorEnrollmentDto;

    public function verifyChallenge(AccessEntity $user, string $code): bool;

    public function disableSecondFactor(AccessEntity $user): void;
}
