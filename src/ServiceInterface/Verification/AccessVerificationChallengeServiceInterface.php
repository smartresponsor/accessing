<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Verification;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\Entity\AccessUserEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessVerificationChallengeServiceInterface
{
    public function issueEmailVerification(AccessUserEntity $user, ?Request $request = null): AccessIssuedChallengeDto;

    public function issuePhoneVerification(AccessUserEntity $user, string $phoneNumber, ?Request $request = null): AccessIssuedChallengeDto;

    public function issuePasswordRecovery(AccessUserEntity $user, ?Request $request = null): AccessIssuedChallengeDto;

    public function completeEmailVerification(AccessUserEntity $user, string $code): bool;

    public function completePhoneVerification(AccessUserEntity $user, string $code): bool;

    public function consumePasswordRecovery(AccessUserEntity $user, string $code): bool;

    public function cleanupExpiredChallenges(): int;
}
