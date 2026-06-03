<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecondFactor;

use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessAccountEntity;

interface AccessSecondFactorServiceInterface
{
    public function beginEnrollment(AccessAccountEntity $account): AccessSecondFactorEnrollmentDto;

    public function confirmEnrollment(AccessAccountEntity $account, string $code): ?AccessSecondFactorEnrollmentDto;

    public function verifyChallenge(AccessAccountEntity $account, string $code): bool;

    public function disableSecondFactor(AccessAccountEntity $account): void;
}
