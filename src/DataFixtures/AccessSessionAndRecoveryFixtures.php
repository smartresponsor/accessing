<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessResetPasswordRequestEntity;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Entity\AccessUserSessionEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessSessionAndRecoveryFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new AccessUserEntity()
            ->setEmail('session-demo@smartresponsor.local')
            ->setDisplayName('Accessing Session Demo')
            ->setRoles(['ROLE_USER'])
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new AccessUserEntity(), 'AccessingSession123!'));
        $user->markEmailVerified();

        $userSession = new AccessUserSessionEntity()
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
