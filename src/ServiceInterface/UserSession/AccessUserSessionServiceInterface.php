<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\UserSession;

use App\Accessing\Entity\AccessUserEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessUserSessionServiceInterface
{
    public function registerSession(AccessUserEntity $user, Request $request): void;

    public function invalidateCurrentSession(AccessUserEntity $user, SessionInterface $session): void;

    public function invalidateOtherSessions(AccessUserEntity $user, SessionInterface $session): int;

    public function cleanupSessions(): int;
}
