<?php

declare(strict_types=1);

namespace App\ServiceInterface\Account;

use App\Dto\AccessingSignInResultDto;
use App\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessingAccountAuthenticationServiceInterface
{
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessingSignInResultDto;

    public function completePendingSecondFactor(Account $account, Request $request): void;

    public function signOut(?Account $account, Request $request): void;

    public function getPendingSecondFactorAccountId(SessionInterface $session): ?int;

    public function clearPendingSecondFactor(SessionInterface $session): void;
}
