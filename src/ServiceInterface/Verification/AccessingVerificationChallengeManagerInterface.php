<?php

declare(strict_types=1);

namespace App\ServiceInterface\Verification;

use App\Entity\Account;
use App\Entity\VerificationChallenge;

interface AccessingVerificationChallengeManagerInterface
{
    public function createEmailChallenge(Account $account): VerificationChallenge;

    public function createPhoneChallenge(Account $account, string $phoneNumber): VerificationChallenge;
}
