<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Command\AccessEnsureAdminCommand;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AccessEnsureAdminCommandTest extends TestCase
{
    public function testExistingAdministratorPasswordIsNotChangedByDefault(): void
    {
        $user = new AccessEntity('admin@smartresponsor.local', 'Accessing Admin');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->willReturn($user);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::never())->method('changePassword');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester(new AccessEnsureAdminCommand($repository, $entityManager, $credentials));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testNewAdministratorRequiresPassword(): void
    {
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->willReturn(null);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::never())->method('changePassword');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $tester = new CommandTester(new AccessEnsureAdminCommand($repository, $entityManager, $credentials));

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('--password', $tester->getDisplay());
    }

    public function testExistingAdministratorCanBeExplicitlyReset(): void
    {
        $user = new AccessEntity('admin@smartresponsor.local', 'Accessing Admin');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findOneByEmailAddress')->willReturn($user);
        $credentials = $this->createMock(AccessCredentialServiceInterface::class);
        $credentials->expects(self::once())->method('changePassword')->with($user, 'replacement-password');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester(new AccessEnsureAdminCommand($repository, $entityManager, $credentials));

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--reset-password' => true,
            '--password' => 'replacement-password',
        ]));
    }

    public function testKnownDefaultPasswordIsAbsentFromCommandSource(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/Command/AccessEnsureAdminCommand.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('ADMIN_PASSWORD', $source);
        self::assertStringNotContainsString("= 'admin'", $source);
    }
}
