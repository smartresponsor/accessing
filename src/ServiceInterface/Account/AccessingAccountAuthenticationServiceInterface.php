<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Account;

use App\Accessing\Dto\AccessingSignInResultDto;
use App\Accessing\Entity\AccessAccountEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessingAccountAuthenticationServiceInterface
{
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessingSignInResultDto;

    public function completePendingSecondFactor(AccessAccountEntity $account, Request $request): void;

    public function signOut(?AccessAccountEntity $account, Request $request): void;

    public function getPendingSecondFactorAccountId(SessionInterface $session): ?int;

    public function clearPendingSecondFactor(SessionInterface $session): void;
}
