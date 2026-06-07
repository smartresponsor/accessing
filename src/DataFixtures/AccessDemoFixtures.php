<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessDemoFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new AccessEntity()
            ->setEmail('demo@smartresponsor.local')
            ->setDisplayName('Accessing Demo')
            ->setPhoneNumber('+13468832743')
            ->setRoles(['ROLE_USER'])
            ->setSecondFactorEnabled(true)
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new AccessEntity(), 'AccessingDemo123!'));
        $user->markEmailVerified();

        $manager->persist($user);

        $emailChallenge = new AccessVerificationChallengeEntity()
            ->setUser($user)
            ->setChannelType('email')
            ->setTarget($user->getEmail())
            ->setToken('demo-email-token');
        $emailChallenge->markCompleted();

        $phoneChallenge = new AccessVerificationChallengeEntity()
            ->setUser($user)
            ->setChannelType('phone')
            ->setTarget((string) $user->getPhoneNumber())
            ->setToken('demo-phone-token');

        $recoveryCode = new AccessRecoveryCodeEntity()
            ->setUser($user)
            ->setCodeHash(hash('sha256', 'DEMO-RECOVERY-CODE-1'));

        $securityEvent = new AccessSecurityEventEntity()
            ->setUser($user)
            ->setEventType('user.registered')
            ->setContext([
                'fixture' => true,
                'channel' => 'demo',
            ])
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('AccessDemoFixtures/1.0');

        $manager->persist($emailChallenge);
        $manager->persist($phoneChallenge);
        $manager->persist($recoveryCode);
        $manager->persist($securityEvent);
        $manager->flush();
    }
}
