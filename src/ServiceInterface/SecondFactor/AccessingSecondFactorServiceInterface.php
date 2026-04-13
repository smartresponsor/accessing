<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\ServiceInterface\SecondFactor;

use App\Dto\AccessingSecondFactorEnrollmentDto;
use App\Entity\Account;

interface AccessingSecondFactorServiceInterface
{
    public function beginEnrollment(Account $account): AccessingSecondFactorEnrollmentDto;

    public function confirmEnrollment(Account $account, string $code): ?AccessingSecondFactorEnrollmentDto;

    public function verifyChallenge(Account $account, string $code): bool;

    public function disableSecondFactor(Account $account): void;
}
