<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessUserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:user:diagnostics', description: 'Print a concise Accessing user and trust summary.')]
final class AccessUserDiagnosticsCommand extends Command
{
    public function __construct(
        private readonly AccessUserRepositoryInterface $userRepository,
        private readonly AccessSecurityEventRepositoryInterface $securityEventRepository,
    ) {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findRecentUsers(250);

        $io->definitionList(
            ['Users', (string) count($users)],
            ['Recently tracked security events', (string) count($this->securityEventRepository->findRecentEvents(25))],
            ['Locked users', (string) count(array_filter($users, static fn ($user) => $user->isLocked()))],
            ['Email verified users', (string) count(array_filter($users, static fn ($user) => $user->isEmailVerified()))],
            ['Second factor enabled users', (string) count(array_filter($users, static fn ($user) => $user->getSecondFactor()?->isEnabled() ?? false))],
        );

        return Command::SUCCESS;
    }
}
