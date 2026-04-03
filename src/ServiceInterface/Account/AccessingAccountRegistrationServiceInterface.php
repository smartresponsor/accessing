<?php

declare(strict_types=1);

namespace App\ServiceInterface\Account;

use App\Dto\AccessingIssuedChallengeDto;
use App\Entity\Account;
use Symfony\Component\HttpFoundation\Request;

interface AccessingAccountRegistrationServiceInterface
{
    /**
     * @return array{account: Account, emailChallenge: AccessingIssuedChallengeDto}
     */
    public function register(string $displayName, string $emailAddress, string $plainPassword, ?Request $request = null): array;
}
