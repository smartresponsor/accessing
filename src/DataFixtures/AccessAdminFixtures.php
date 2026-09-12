<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Defines the admin fixtures type and its canonical responsibility within the Accessing component.
 */
final class AccessAdminFixtures extends Fixture
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private readonly AccessRepositoryInterface $userRepository,
        private readonly AccessCredentialServiceInterface $credentialService,
        private readonly string $adminPassword = '',
    ) {
    }

    /**
     * Executes the assert configured operation within the canonical Accessing component workflow.
     */
    public function assertConfigured(): void
    {
        if ('' === trim($this->adminPassword)) {
            throw new \RuntimeException('A non-empty Accessing administrator password must be configured before loading admin fixtures.');
        }
    }

    /**
     * Executes the load operation within the canonical Accessing component workflow.
     */
    public function load(ObjectManager $manager): void
    {
        $this->assertConfigured();
        $adminPassword = trim($this->adminPassword);

        $user = $this->userRepository->findOneByEmailAddress(self::ADMIN_EMAIL)
            ?? new AccessEntity();

        $user
            ->setEmail(self::ADMIN_EMAIL)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN_BOOTSTRAP', 'ROLE_ALLOWED_TO_SWITCH'])
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        $manager->persist($user);
        $this->credentialService->changePassword($user, $adminPassword);
        $manager->flush();
    }
}
