<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Verification;

use App\Accessing\Dto\AccessingIssuedChallengeDto;
use App\Accessing\Entity\Account;
use Symfony\Component\HttpFoundation\Request;

interface AccessingVerificationChallengeServiceInterface
{
    public function issueEmailVerification(Account $account, ?Request $request = null): AccessingIssuedChallengeDto;

    public function issuePhoneVerification(Account $account, string $phoneNumber, ?Request $request = null): AccessingIssuedChallengeDto;

    public function issuePasswordRecovery(Account $account, ?Request $request = null): AccessingIssuedChallengeDto;

    public function completeEmailVerification(Account $account, string $code): bool;

    public function completePhoneVerification(Account $account, string $code): bool;

    public function consumePasswordRecovery(Account $account, string $code): bool;

    public function cleanupExpiredChallenges(): int;
}
