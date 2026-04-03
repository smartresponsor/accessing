<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\RecoveryCode;
use App\Entity\SecurityEvent;
use App\Entity\VerificationChallenge;
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
        $account = (new Account())
            ->setEmail('demo@smartresponsor.local')
            ->setDisplayName('Accessing Demo')
            ->setPhoneNumber('+13468832743')
            ->setRoles(['ROLE_USER'])
            ->setSecondFactorEnabled(true)
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new Account(), 'AccessingDemo123!'));
        $account->markEmailVerified();

        $manager->persist($account);

        $emailChallenge = (new VerificationChallenge())
            ->setAccount($account)
            ->setChannelType('email')
            ->setTarget($account->getEmail())
            ->setToken('demo-email-token');
        $emailChallenge->markCompleted();

        $phoneChallenge = (new VerificationChallenge())
            ->setAccount($account)
            ->setChannelType('phone')
            ->setTarget((string) $account->getPhoneNumber())
            ->setToken('demo-phone-token');

        $recoveryCode = (new RecoveryCode())
            ->setAccount($account)
            ->setCodeHash(hash('sha256', 'DEMO-RECOVERY-CODE-1'));

        $securityEvent = (new SecurityEvent())
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
