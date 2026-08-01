<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessExternalIdentityEntity;

interface AccessExternalIdentityRepositoryInterface
{
    public function findOneByProviderAndSubject(string $provider, string $subject): ?AccessExternalIdentityEntity;

    public function save(AccessExternalIdentityEntity $identity, bool $flush = false): void;
}
