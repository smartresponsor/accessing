<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccessingAdminFixtures extends Fixture
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';
    private const ADMIN_PASSWORD = 'admin';

    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccessingCredentialServiceInterface $credentialService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $account = $this->accountRepository->findOneByEmailAddress(self::ADMIN_EMAIL)
            ?? new AccessAccountEntity();

        $account
            ->setEmail(self::ADMIN_EMAIL)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
            ->setSecondFactorEnabled(false)
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        $this->credentialService->changePassword($account, self::ADMIN_PASSWORD);

        $manager->persist($account);
        $manager->flush();
    }
}
