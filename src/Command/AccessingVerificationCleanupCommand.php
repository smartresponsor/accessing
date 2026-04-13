<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Command;

use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:verification:cleanup', description: 'Remove expired and spent verification challenges.')]
final class AccessingVerificationCleanupCommand extends Command
{
    public function __construct(
        private readonly AccessingVerificationChallengeServiceInterface $verificationChallengeService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $removedCount = $this->verificationChallengeService->cleanupExpiredChallenges();
        $io->success(sprintf('Removed %d expired or consumed verification challenge record(s).', $removedCount));

        return Command::SUCCESS;
    }
}
