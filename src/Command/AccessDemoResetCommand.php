<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\DataFixtures\AccessAdminFixtures;
use App\Accessing\DataFixtures\AccessDemoFixtures;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'accessing:demo:reset', description: 'Rebuild the schema and load demo fixtures for Accessing.')]
final class AccessDemoResetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessAdminFixtures $accessingAdminFixtures,
        private readonly AccessDemoFixtures $accessingDemoFixtures,
        private readonly KernelInterface $kernel,
        private readonly ?SymfonyFixturesLoader $fixturesLoader = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Confirm destructive demo database reset.');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $environment = $this->kernel->getEnvironment();

        if (!in_array($environment, ['dev', 'test'], true)) {
            $io->error(sprintf('Refusing to reset the Accessing demo database in the "%s" environment.', $environment));

            return Command::FAILURE;
        }

        if (!(bool) $input->getOption('force')) {
            $io->error('The --force option is required for this destructive operation.');

            return Command::INVALID;
        }

        if ($input->isInteractive() && !$io->confirm(sprintf(
            'This will drop and rebuild the entire database for the "%s" environment. Continue?',
            $environment,
        ), false)) {
            $io->warning('Accessing demo database reset was cancelled.');

            return Command::FAILURE;
        }

        if (!$this->fixturesLoader instanceof SymfonyFixturesLoader) {
            $io->error('Doctrine fixtures loader is not available in this environment.');

            return Command::FAILURE;
        }

        try {
            $this->accessingAdminFixtures->assertConfigured();
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        $loader = clone $this->fixturesLoader;
        $loader->addFixture($this->accessingAdminFixtures);
        $loader->addFixture($this->accessingDemoFixtures);

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute($loader->getFixtures());

        $io->success(sprintf(
            'Accessing demo database for the "%s" environment has been rebuilt and repopulated.',
            $environment,
        ));

        return Command::SUCCESS;
    }
}
