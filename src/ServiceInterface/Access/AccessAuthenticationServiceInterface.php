<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Access;

use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessAuthenticationServiceInterface
{
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessSignInResultDto;

    public function completePendingSecondFactor(AccessEntity $user, Request $request): void;

    public function signOut(?AccessEntity $user, Request $request): void;

    public function getPendingSecondFactorUserId(SessionInterface $session): ?int;

    public function clearPendingSecondFactor(SessionInterface $session): void;
}
