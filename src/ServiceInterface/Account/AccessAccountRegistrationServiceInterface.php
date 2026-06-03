<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Account;

use App\Accessing\Dto\AccessAccountRegistrationRequest;
use App\Accessing\Entity\AccessAccountEntity;

interface AccessAccountRegistrationServiceInterface
{
    public function register(AccessAccountRegistrationRequest $request): AccessAccountEntity;
}
