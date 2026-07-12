<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;

interface AccessPasskeyCredentialRepositoryInterface
{
    public function save(AccessPasskeyCredentialEntity $credential, bool $flush = false): void;

    public function findOneByCredentialId(string $credentialId): ?AccessPasskeyCredentialEntity;

    /** @return list<AccessPasskeyCredentialEntity> */
    public function findActiveForUser(AccessEntity $user): array;
}
