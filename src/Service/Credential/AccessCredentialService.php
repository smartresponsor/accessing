<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Defines the credential service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessCredentialService implements AccessCredentialServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AccessCompromisedPasswordProviderInterface $compromisedPasswordProvider,
    ) {
    }

    /**
     * Executes the create credential operation within the canonical Accessing component workflow.
     */
    public function createCredential(AccessEntity $user, string $plainPassword): AccessCredentialEntity
    {
        $this->assertPasswordIsSafe($plainPassword);
        $passwordHash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $credential = new AccessCredentialEntity($user, $passwordHash);
        $user->setCredential($credential);
        $this->entityManager->persist($credential);

        return $credential;
    }

    /**
     * Executes the verify password operation within the canonical Accessing component workflow.
     */
    public function verifyPassword(AccessEntity $user, string $plainPassword): bool
    {
        return $user->getCredential() instanceof AccessCredentialEntity
            && $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * Executes the change password operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the assert password is safe operation within the canonical Accessing component workflow.
     */
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
