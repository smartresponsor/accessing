<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Command\AccessDemoResetCommand;
use App\Accessing\DataFixtures\AccessAdminFixtures;
use App\Accessing\DataFixtures\AccessDemoFixtures;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

final class AccessDemoResetCommandTest extends TestCase
{
    public function testProductionEnvironmentIsAlwaysRejected(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getMetadataFactory');

        $tester = new CommandTester($this->command('prod', $entityManager));

        self::assertSame(Command::FAILURE, $tester->execute(['--force' => true]));
        self::assertStringContainsString('Refusing to reset', $tester->getDisplay());
    }

    public function testForceOptionIsRequiredInAllowedEnvironment(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getMetadataFactory');

        $tester = new CommandTester($this->command('test', $entityManager));

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testInteractiveResetCanBeCancelledBeforeDatabaseAccess(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getMetadataFactory');

        $tester = new CommandTester($this->command('dev', $entityManager));
        $tester->setInputs(['no']);

        self::assertSame(Command::FAILURE, $tester->execute(['--force' => true], ['interactive' => true]));
        self::assertStringContainsString('cancelled', $tester->getDisplay());
    }

    private function command(string $environment, EntityManagerInterface $entityManager): AccessDemoResetCommand
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn($environment);

        return new AccessDemoResetCommand(
            $entityManager,
            new AccessAdminFixtures(
                $this->createMock(AccessRepositoryInterface::class),
                $this->createMock(AccessCredentialServiceInterface::class),
                'test-only-admin-password',
            ),
            new AccessDemoFixtures($this->createMock(AccessCredentialServiceInterface::class)),
            $kernel,
        );
    }
}
