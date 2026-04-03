<?php

declare(strict_types=1);

namespace App\Command;

use App\ServiceInterface\AccountSession\AccessingAccountSessionServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:session:cleanup', description: 'Remove stale invalidated account sessions.')]
final class AccessingSessionCleanupCommand extends Command
{
    public function __construct(
        private readonly AccessingAccountSessionServiceInterface $accountSessionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $removedCount = $this->accountSessionService->cleanupSessions();
        $io->success(sprintf('Removed %d stale session record(s).', $removedCount));

        return Command::SUCCESS;
    }
}
