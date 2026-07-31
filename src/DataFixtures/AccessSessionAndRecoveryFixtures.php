<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessResetPasswordRequestEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccessSessionAndRecoveryFixtures extends Fixture
{
    public function __construct(
        private readonly AccessCredentialServiceInterface $credentialService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new AccessEntity()
            ->setEmail('session-demo@smartresponsor.local')
            ->setDisplayName('Accessing Session Demo')
            ->setRoles(['ROLE_USER']);
        $user->markEmailVerified();
        $manager->persist($user);
        $this->credentialService->changePassword($user, 'AccessingSession123!');

        $userSession = new AccessSessionEntity()
            ->setUser($user)
            ->setSessionIdentifier('demo-session-identifier')
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('AccessSessionAndRecoveryFixtures/1.0')
            ->setTrusted(true);

        $resetPasswordRequest = new AccessResetPasswordRequestEntity(
            $user,
            new \DateTimeImmutable('+1 hour'),
            'selector-demo',
            hash('sha256', 'reset-demo-token')
        );

        $manager->persist($user);
        $manager->persist($userSession);
        $manager->persist($resetPasswordRequest);
        $manager->flush();
    }
}
