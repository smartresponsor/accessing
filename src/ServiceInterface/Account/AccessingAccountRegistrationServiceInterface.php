<?php

declare(strict_types=1);

namespace App\ServiceInterface\Account;

use App\Dto\AccountRegistrationRequest;
use App\Entity\Account;

interface AccessingAccountRegistrationServiceInterface
{
    public function register(AccountRegistrationRequest $request): Account;
}
