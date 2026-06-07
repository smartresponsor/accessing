<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\User;

use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessUserEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessUserAuthenticationServiceInterface
{
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessSignInResultDto;

    public function completePendingSecondFactor(AccessUserEntity $user, Request $request): void;

    public function signOut(?AccessUserEntity $user, Request $request): void;

    public function getPendingSecondFactorUserId(SessionInterface $session): ?int;

    public function clearPendingSecondFactor(SessionInterface $session): void;
}
