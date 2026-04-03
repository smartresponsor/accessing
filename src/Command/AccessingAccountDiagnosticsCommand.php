<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AccountRepository;
use App\Repository\SecurityEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:account:diagnostics', description: 'Print a concise Accessing account and trust summary.')]
final class AccessingAccountDiagnosticsCommand extends Command
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly SecurityEventRepository $securityEventRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $accounts = $this->accountRepository->findRecentAccounts(250);

        $io->definitionList(
            ['Accounts', (string) count($accounts)],
            ['Recently tracked security events', (string) count($this->securityEventRepository->findRecentEvents(25))],
            ['Locked accounts', (string) count(array_filter($accounts, static fn($account) => $account->isLocked()))],
            ['Email verified accounts', (string) count(array_filter($accounts, static fn($account) => $account->isEmailVerified()))],
            ['Second factor enabled accounts', (string) count(array_filter($accounts, static fn($account) => $account->getSecondFactor()?->isEnabled() ?? false))],
        );

        return Command::SUCCESS;
    }
}
