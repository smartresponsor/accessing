<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Credential;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AccessingCredentialService implements AccessingCredentialServiceInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createCredential(AccessAccountEntity $account, string $plainPassword): AccessCredentialEntity
    {
        $passwordHash = $this->passwordHasher->hashPassword($account, $plainPassword);
        $account->setPasswordHash($passwordHash);

        $credential = new AccessCredentialEntity($account, $passwordHash);
        $account->setCredential($credential);
        $this->entityManager->persist($credential);

        return $credential;
    }

    public function verifyPassword(AccessAccountEntity $account, string $plainPassword): bool
    {
        return $account->getCredential() instanceof AccessCredentialEntity
            && $this->passwordHasher->isPasswordValid($account, $plainPassword);
    }

    public function changePassword(AccessAccountEntity $account, string $plainPassword): void
    {
        $credential = $account->getCredential();

        if (!$credential instanceof AccessCredentialEntity) {
            $credential = $this->createCredential($account, $plainPassword);
        }

        $passwordHash = $this->passwordHasher->hashPassword($account, $plainPassword);
        $account->setPasswordHash($passwordHash);
        $credential->updatePasswordHash($passwordHash);
        $this->entityManager->persist($credential);
        $this->entityManager->flush();
    }
}
