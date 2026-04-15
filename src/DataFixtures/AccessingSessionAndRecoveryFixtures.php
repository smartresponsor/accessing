<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\AccountSession;
use App\Entity\ResetPasswordRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessingSessionAndRecoveryFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $account = new Account()
            ->setEmail('session-demo@smartresponsor.local')
            ->setDisplayName('Accessing Session Demo')
            ->setRoles(['ROLE_ACCOUNT'])
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new Account(), 'AccessingSession123!'));
        $account->markEmailVerified();

        $accountSession = new AccountSession()
            ->setAccount($account)
            ->setSessionIdentifier('demo-session-identifier')
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('AccessingSessionAndRecoveryFixtures/1.0')
            ->setTrusted(true);

        $resetPasswordRequest = new ResetPasswordRequest(
            $account,
            new \DateTimeImmutable('+1 hour'),
            'selector-demo',
            hash('sha256', 'reset-demo-token')
        );

        $manager->persist($account);
        $manager->persist($accountSession);
        $manager->persist($resetPasswordRequest);
        $manager->flush();
    }
}
