<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Recovery;

use App\Accessing\Dto\AccessingIssuedChallengeDto;
use Symfony\Component\HttpFoundation\Request;

interface AccessingRecoveryServiceInterface
{
    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessingIssuedChallengeDto;

    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool;
}
