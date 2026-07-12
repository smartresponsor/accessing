<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DataFixtures\AccessAdminFixtures;
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

    public function testKnownDefaultPasswordIsAbsentFromFixtureSource(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/DataFixtures/AccessAdminFixtures.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('ADMIN_PASSWORD', $source);
        self::assertStringNotContainsString("= 'admin'", $source);
    }
}
