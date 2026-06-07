<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Access;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Entity\AccessEntity;

interface AccessRegistrationServiceInterface
{
    public function register(AccessRegistrationRequest $request): AccessEntity;
}
