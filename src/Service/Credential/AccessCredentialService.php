<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AccessCredentialService implements AccessCredentialServiceInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AccessCompromisedPasswordProviderInterface $compromisedPasswordProvider,
    ) {
    }

    public function createCredential(AccessEntity $user, string $plainPassword): AccessCredentialEntity
    {
        $this->assertPasswordIsSafe($plainPassword);
        $passwordHash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $credential = new AccessCredentialEntity($user, $passwordHash);
        $user->setCredential($credential);
        $this->entityManager->persist($credential);

        return $credential;
    }

    public function verifyPassword(AccessEntity $user, string $plainPassword): bool
    {
        return $user->getCredential() instanceof AccessCredentialEntity
            && $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function changePassword(AccessEntity $user, string $plainPassword): void
    {
        $credential = $user->getCredential();

        if (!$credential instanceof AccessCredentialEntity) {
            $this->createCredential($user, $plainPassword);
            $this->entityManager->flush();

            return;
        }

        $this->assertPasswordIsSafe($plainPassword);
        $passwordHash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $credential->updatePasswordHash($passwordHash);
        $this->entityManager->persist($credential);
        $this->entityManager->flush();
    }

    private function assertPasswordIsSafe(string $plainPassword): void
    {
        $status = $this->compromisedPasswordProvider->check($plainPassword)->status;

        if (AccessPasswordSafetyStatus::Compromised === $status) {
            throw new AccessCompromisedPasswordException();
        }

        if (AccessPasswordSafetyStatus::Unavailable === $status) {
            throw new AccessPasswordSafetyUnavailableException();
        }
    }
}
