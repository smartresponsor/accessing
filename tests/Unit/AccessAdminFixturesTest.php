<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DataFixtures\AccessAdminFixtures;
use App\Accessing\DataFixtures\AccessDemoFixtures;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

final class AccessAdminFixturesTest extends TestCase
{
    public function testFixtureRejectsMissingAdministratorPasswordBeforeMutation(): void
    {
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->expects(self::never())->method('findOneByEmailAddress');

        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::never())->method('changePassword');

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $fixture = new AccessAdminFixtures($repository, $credentials);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('administrator password must be configured');

        $fixture->load($manager);
    }

    public function testFixtureRejectsWhitespaceOnlyAdministratorPassword(): void
    {
        $fixture = new AccessAdminFixtures(
            $this->createMock(AccessRepositoryInterface::class),
            $this->createMock(AccessCredentialServiceInterface::class),
            '   ',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('administrator password must be configured');
        $fixture->assertConfigured();
    }

    public function testFixtureUsesExplicitAdministratorPassword(): void
    {
        $user = new AccessEntity();
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findOneByEmailAddress')
            ->with('admin@smartresponsor.local')
            ->willReturn($user);

        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::once())
            ->method('changePassword')
            ->with($user, 'fixture-secret-password');

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $fixture = new AccessAdminFixtures($repository, $credentials, 'fixture-secret-password');
        $fixture->load($manager);
    }

    public function testFixtureCreatesAdministratorWhenRepositoryHasNoExistingUser(): void
    {
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->with('admin@smartresponsor.local')->willReturn(null);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::once())->method('changePassword')->with(
            self::callback(static fn (AccessEntity $user): bool => 'admin@smartresponsor.local' === $user->getEmail()),
            'new-admin-password',
        );
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->with(self::isInstanceOf(AccessEntity::class));
        $manager->expects(self::once())->method('flush');

        $fixture = new AccessAdminFixtures($repository, $credentials, ' new-admin-password ');
        $fixture->assertConfigured();
        $fixture->load($manager);
    }

    public function testDemoFixtureCreatesFullDemoIdentityOnFirstLoad(): void
    {
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->with('demo@smartresponsor.local')->willReturn(null);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::never())->method('verifyPassword');
        $credentials->expects(self::once())->method('changePassword')->with(
            self::isInstanceOf(AccessEntity::class),
            'AccessingDemo123!',
        );
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::exactly(5))->method('persist');
        $manager->expects(self::exactly(2))->method('flush');

        (new AccessDemoFixtures($repository, $credentials))->load($manager);
    }

    public function testDemoFixtureLeavesExistingVerifiedIdentityAndMatchingCredentialIntact(): void
    {
        $user = new AccessEntity('demo@smartresponsor.local', 'Existing Demo');
        $user->markEmailVerified();
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->willReturn($user);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::once())->method('verifyPassword')->with($user, 'AccessingDemo123!')->willReturn(true);
        $credentials->expects(self::never())->method('changePassword');
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        (new AccessDemoFixtures($repository, $credentials))->load($manager);
        self::assertTrue($user->isEmailVerified());
    }

    public function testDemoFixtureRepairsExistingUnverifiedIdentityAndCredential(): void
    {
        $user = new AccessEntity('demo@smartresponsor.local', 'Existing Demo');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->willReturn($user);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::once())->method('verifyPassword')->with($user, 'AccessingDemo123!')->willReturn(false);
        $credentials->expects(self::once())->method('changePassword')->with($user, 'AccessingDemo123!');
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        (new AccessDemoFixtures($repository, $credentials))->load($manager);
        self::assertTrue($user->isEmailVerified());
    }

    public function testKnownDefaultPasswordIsAbsentFromFixtureSource(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/DataFixtures/AccessAdminFixtures.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('ADMIN_PASSWORD', $source);
        self::assertStringNotContainsString("= 'admin'", $source);
    }
}
