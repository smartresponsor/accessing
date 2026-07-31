<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Session;

use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AccessSessionServiceInterface
{
    public function registerSession(AccessEntity $user, Request $request, bool $flush = true): void;

    public function invalidateCurrentSession(AccessEntity $user, SessionInterface $session): void;

    public function invalidateOtherSessions(AccessEntity $user, SessionInterface $session): int;

    public function cleanupSessions(): int;
}
