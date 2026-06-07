<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\RepositoryInterface\AccessUserRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccessAdminFixtures extends Fixture
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';
    private const ADMIN_PASSWORD = 'admin';

    public function __construct(
        private readonly AccessUserRepositoryInterface $userRepository,
        private readonly AccessCredentialServiceInterface $credentialService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->userRepository->findOneByEmailAddress(self::ADMIN_EMAIL)
            ?? new AccessUserEntity();

        $user
            ->setEmail(self::ADMIN_EMAIL)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
            ->setSecondFactorEnabled(false)
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        $this->credentialService->changePassword($user, self::ADMIN_PASSWORD);

        $manager->persist($user);
        $manager->flush();
    }
}
