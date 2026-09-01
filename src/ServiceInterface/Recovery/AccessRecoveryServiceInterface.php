<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Recovery;

use App\Accessing\Dto\AccessIssuedChallenge;
use Symfony\Component\HttpFoundation\Request;

interface AccessRecoveryServiceInterface
{
    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessIssuedChallenge;

    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool;
}
