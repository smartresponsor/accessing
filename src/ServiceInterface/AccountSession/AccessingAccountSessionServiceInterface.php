<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\AccountSession;

use App\Accessing\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessingAccountSessionServiceInterface
{
    public function registerSession(Account $account, Request $request): void;

    public function invalidateCurrentSession(Account $account, SessionInterface $session): void;

    public function invalidateOtherSessions(Account $account, SessionInterface $session): int;

    public function cleanupSessions(): int;
}
