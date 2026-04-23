<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessingDemoFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $account = new AccessAccountEntity()
            ->setEmail('demo@smartresponsor.local')
            ->setDisplayName('Accessing Demo')
            ->setPhoneNumber('+13468832743')
            ->setRoles(['ROLE_ACCOUNT'])
            ->setSecondFactorEnabled(true)
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new AccessAccountEntity(), 'AccessingDemo123!'));
        $account->markEmailVerified();

        $manager->persist($account);

        $emailChallenge = new AccessVerificationChallengeEntity()
            ->setAccount($account)
            ->setChannelType('email')
            ->setTarget($account->getEmail())
            ->setToken('demo-email-token');
        $emailChallenge->markCompleted();

        $phoneChallenge = new AccessVerificationChallengeEntity()
            ->setAccount($account)
            ->setChannelType('phone')
            ->setTarget((string) $account->getPhoneNumber())
            ->setToken('demo-phone-token');

        $recoveryCode = new AccessRecoveryCodeEntity()
            ->setAccount($account)
            ->setCodeHash(hash('sha256', 'DEMO-RECOVERY-CODE-1'));

        $securityEvent = new AccessSecurityEventEntity()
            ->setAccount($account)
            ->setEventType('account.registered')
            ->setContext([
                'fixture' => true,
                'channel' => 'demo',
            ])
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('AccessingDemoFixtures/1.0');

        $manager->persist($emailChallenge);
        $manager->persist($phoneChallenge);
        $manager->persist($recoveryCode);
        $manager->persist($securityEvent);
        $manager->flush();
    }
}
