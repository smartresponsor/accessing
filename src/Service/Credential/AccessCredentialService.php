<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AccessCredentialService implements AccessCredentialServiceInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createCredential(AccessUserEntity $user, string $plainPassword): AccessCredentialEntity
    {
        $passwordHash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPasswordHash($passwordHash);

        $credential = new AccessCredentialEntity($user, $passwordHash);
        $user->setCredential($credential);
        $this->entityManager->persist($credential);

        return $credential;
    }

    public function verifyPassword(AccessUserEntity $user, string $plainPassword): bool
    {
        return $user->getCredential() instanceof AccessCredentialEntity
            && $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function changePassword(AccessUserEntity $user, string $plainPassword): void
    {
        $credential = $user->getCredential();

        if (!$credential instanceof AccessCredentialEntity) {
            $credential = $this->createCredential($user, $plainPassword);
        }

        $passwordHash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPasswordHash($passwordHash);
        $credential->updatePasswordHash($passwordHash);
        $this->entityManager->persist($credential);
        $this->entityManager->flush();
    }
}
