<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecondFactor;

use App\Accessing\Dto\AccessingSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessAccountEntity;

interface AccessingSecondFactorServiceInterface
{
    public function beginEnrollment(AccessAccountEntity $account): AccessingSecondFactorEnrollmentDto;

    public function confirmEnrollment(AccessAccountEntity $account, string $code): ?AccessingSecondFactorEnrollmentDto;

    public function verifyChallenge(AccessAccountEntity $account, string $code): bool;

    public function disableSecondFactor(AccessAccountEntity $account): void;
}
