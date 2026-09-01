<?php

declare(strict_types=1);

namespace App\Accessing\DataFixtures;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class AccessMarketplaceFixtures extends Fixture implements FixtureGroupInterface
{
    private const PASSWORD = 'MarketplaceDemo123!';

    private const USERS = [
        ['alex.pro@smartresponsor.local', 'Alex Morgan', ['ROLE_USER', 'ROLE_PRO']],
        ['maria.pro@smartresponsor.local', 'Maria Hernandez', ['ROLE_USER', 'ROLE_PRO']],
        ['daniel.pro@smartresponsor.local', 'Daniel Brooks', ['ROLE_USER', 'ROLE_PRO']],
        ['emily.customer@smartresponsor.local', 'Emily Carter', ['ROLE_USER']],
        ['james.customer@smartresponsor.local', 'James Wilson', ['ROLE_USER']],
        ['sophia.customer@smartresponsor.local', 'Sophia Nguyen', ['ROLE_USER']],
        ['michael.customer@smartresponsor.local', 'Michael Reed', ['ROLE_USER']],
    ];

    public function __construct(
        private readonly AccessRepositoryInterface $accessRepository,
        private readonly AccessCredentialServiceInterface $credentialService,
    ) {
    }

    public static function getGroups(): array
    {
        return ['accessing_marketplace'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as [$email, $displayName, $roles]) {
            $user = $this->accessRepository->findOneByEmailAddress($email) ?? new AccessEntity();
            $user
                ->setEmail($email)
                ->setDisplayName($displayName)
                ->setRoles($roles)
                ->unlock()
                ->resetFailedLoginCount();

            if (!$user->isEmailVerified()) {
                $user->markEmailVerified();
            }

            $manager->persist($user);
            $manager->flush();

            if (!$this->credentialService->verifyPassword($user, self::PASSWORD)) {
                $this->credentialService->changePassword($user, self::PASSWORD);
            }
        }

        $manager->flush();
    }
}
