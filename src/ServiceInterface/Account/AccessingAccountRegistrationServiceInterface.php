<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Account;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Entity\AccessAccountEntity;

interface AccessingAccountRegistrationServiceInterface
{
    public function register(AccountRegistrationRequest $request): AccessAccountEntity;
}
