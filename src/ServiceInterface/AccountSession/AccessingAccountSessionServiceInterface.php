<?php

declare(strict_types=1);

namespace App\ServiceInterface\AccountSession;

use App\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessingAccountSessionServiceInterface
{
    public function registerSession(Account $account, Request $request): void;

    public function invalidateCurrentSession(Account $account, SessionInterface $session): void;

    public function invalidateOtherSessions(Account $account, SessionInterface $session): int;

    public function cleanupSessions(): int;
}
