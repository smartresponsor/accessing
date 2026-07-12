<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Verification;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessVerificationChallengeServiceInterface
{
    public function issueEmailVerification(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDto;

    public function resendEmailVerification(AccessEntity $user, ?Request $request = null): ?AccessIssuedChallengeDto;

    public function issuePhoneVerification(AccessEntity $user, string $phoneNumber, ?Request $request = null): AccessIssuedChallengeDto;

    public function issuePasswordRecovery(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDto;

    public function completeEmailVerification(AccessEntity $user, string $code): bool;

    public function completePhoneVerification(AccessEntity $user, string $code): bool;

    public function consumePasswordRecovery(AccessEntity $user, string $code): bool;

    public function cleanupExpiredChallenges(): int;
}
