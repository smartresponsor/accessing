<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Service\Credential;

use App\Entity\Account;
use App\Entity\Credential;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AccessingCredentialService implements AccessingCredentialServiceInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function createCredential(Account $account, string $plainPassword): Credential
    {
        $credential = new Credential($account, $this->passwordHasher->hashPassword($account, $plainPassword));
        $account->setCredential($credential);
        $this->entityManager->persist($credential);

        return $credential;
    }

    public function verifyPassword(Account $account, string $plainPassword): bool
    {
        return $account->getCredential() instanceof Credential
            && $this->passwordHasher->isPasswordValid($account, $plainPassword);
    }

    public function changePassword(Account $account, string $plainPassword): void
    {
        $credential = $account->getCredential();

        if (!$credential instanceof Credential) {
            $credential = $this->createCredential($account, $plainPassword);
        }

        $credential->updatePasswordHash($this->passwordHasher->hashPassword($account, $plainPassword));
        $this->entityManager->persist($credential);
        $this->entityManager->flush();
    }
}
