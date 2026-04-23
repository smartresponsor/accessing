<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Verification;

use App\Accessing\Dto\AccessingIssuedChallengeDto;
use App\Accessing\Entity\AccessAccountEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessingVerificationChallengeServiceInterface
{
    public function issueEmailVerification(AccessAccountEntity $account, ?Request $request = null): AccessingIssuedChallengeDto;

    public function issuePhoneVerification(AccessAccountEntity $account, string $phoneNumber, ?Request $request = null): AccessingIssuedChallengeDto;

    public function issuePasswordRecovery(AccessAccountEntity $account, ?Request $request = null): AccessingIssuedChallengeDto;

    public function completeEmailVerification(AccessAccountEntity $account, string $code): bool;

    public function completePhoneVerification(AccessAccountEntity $account, string $code): bool;

    public function consumePasswordRecovery(AccessAccountEntity $account, string $code): bool;

    public function cleanupExpiredChallenges(): int;
}
