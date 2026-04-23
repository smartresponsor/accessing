<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\AccountSession;

use App\Accessing\Entity\AccessAccountEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessingAccountSessionServiceInterface
{
    public function registerSession(AccessAccountEntity $account, Request $request): void;

    public function invalidateCurrentSession(AccessAccountEntity $account, SessionInterface $session): void;

    public function invalidateOtherSessions(AccessAccountEntity $account, SessionInterface $session): int;

    public function cleanupSessions(): int;
}
