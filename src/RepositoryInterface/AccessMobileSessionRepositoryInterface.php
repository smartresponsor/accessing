<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessMobileSessionEntity;

interface AccessMobileSessionRepositoryInterface
{
    public function save(AccessMobileSessionEntity $session, bool $flush = false): void;

    public function findOneByAccessTokenHash(string $tokenHash): ?AccessMobileSessionEntity;

    public function findOneByRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity;

    public function findOneByPreviousRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity;
}
