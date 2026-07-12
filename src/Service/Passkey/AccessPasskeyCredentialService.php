<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;

final readonly class AccessPasskeyCredentialService implements AccessPasskeyCredentialServiceInterface
{
    public function __construct(private AccessPasskeyCredentialRepositoryInterface $credentialRepository)
    {
    }

    public function register(
        AccessEntity $user,
        string $credentialId,
        string $userHandle,
        string $publicKey,
        array $transports,
        string $name,
        int $signCount = 0,
        ?string $credentialRecord = null,
    ): AccessPasskeyCredentialEntity {
        if ($this->credentialRepository->findOneByCredentialId($credentialId) instanceof AccessPasskeyCredentialEntity) {
            throw new \DomainException('This passkey credential is already registered.');
        }

        $credential = new AccessPasskeyCredentialEntity($user, $credentialId, $userHandle, $publicKey, $transports, $name, $signCount, $credentialRecord);
        $this->credentialRepository->save($credential, true);

        return $credential;
    }

    public function recordSuccessfulAssertion(string $credentialId, int $signCount, ?string $credentialRecord = null): AccessPasskeyCredentialEntity
    {
        $credential = $this->credentialRepository->findOneByCredentialId($credentialId);
        if (!$credential instanceof AccessPasskeyCredentialEntity || !$credential->isActive()) {
            throw new \DomainException('Passkey credential is unavailable.');
        }

        if (0 === $signCount && 0 === $credential->getSignCount()) {
            $credential->markUsedWithoutCounter();
        } else {
            $credential->advanceSignCount($signCount);
        }

        if (null !== $credentialRecord) {
            $credential->updateCredentialRecord($credentialRecord);
        }

        $this->credentialRepository->save($credential, true);

        return $credential;
    }

    public function revoke(AccessEntity $user, string $credentialId): bool
    {
        $credential = $this->credentialRepository->findOneByCredentialId($credentialId);
        if (!$credential instanceof AccessPasskeyCredentialEntity || $credential->getUser() !== $user) {
            return false;
        }

        $credential->revoke();
        $this->credentialRepository->save($credential, true);

        return true;
    }
}
