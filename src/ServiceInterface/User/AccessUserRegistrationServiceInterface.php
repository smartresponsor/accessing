<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\User;

use App\Accessing\Dto\AccessUserRegistrationRequest;
use App\Accessing\Entity\AccessUserEntity;

interface AccessUserRegistrationServiceInterface
{
    public function register(AccessUserRegistrationRequest $request): AccessUserEntity;
}
