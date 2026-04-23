<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:report:security', description: 'Output the latest Accessing security events in a report-friendly table.')]
final class AccessingReportSecurityCommand extends Command
{
    public function __construct(
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
    ) {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];

        foreach ($this->securityEventRepository->findRecentEvents() as $event) {
            $rows[] = [
                $event->getOccurredAt()->format('Y-m-d H:i:s'),
                $event->getAccount()?->getEmailAddress() ?? 'unknown',
                $event->getEventType()->value,
                $event->getSeverity()->value,
            ];
        }

        $io->table(['Occurred', 'AccessAccountEntity', 'Event', 'Severity'], $rows);

        return Command::SUCCESS;
    }
}
