<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:diagnostics', description: 'Show bootstrap diagnostics for the Accessing component.')]
final class AccessingDiagnosticsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Accessing diagnostics');
        $io->definitionList(
            ['APP_ENV', $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'unknown'],
            ['Database configured', $this->boolLabel((string) ($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? ''))],
            ['Mailer configured', $this->boolLabel((string) ($_SERVER['MAILER_DSN'] ?? $_ENV['MAILER_DSN'] ?? ''))],
            ['Phone verification provider', (string) ($_SERVER['ACCESSING_PHONE_VERIFICATION_PROVIDER'] ?? $_ENV['ACCESSING_PHONE_VERIFICATION_PROVIDER'] ?? 'not-set')],
        );

        $io->comment('This command is intentionally small and safe so the component can expose meaningful CLI diagnostics early.');

        return Command::SUCCESS;
    }

    private function boolLabel(string $value): string
    {
        return trim($value) !== '' ? 'yes' : 'no';
    }
}
