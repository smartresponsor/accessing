<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:session:cleanup', description: 'Remove stale invalidated user sessions.')]
final class AccessSessionCleanupCommand extends Command
{
    public function __construct(
        private readonly AccessSessionServiceInterface $userSessionService,
    ) {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $removedCount = $this->userSessionService->cleanupSessions();
        $io->success(sprintf('Removed %d stale session record(s).', $removedCount));

        return Command::SUCCESS;
    }
}
