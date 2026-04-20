<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:diagnostics', description: 'Show bootstrap diagnostics for the Accessing component.')]
final class AccessingDiagnosticsCommand extends Command
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Accessing diagnostics');
        $io->definitionList(
            ['APP_ENV', $this->stringEnvValue('APP_ENV', 'unknown')],
            ['Database configured', $this->boolLabel($this->stringEnvValue('DATABASE_URL'))],
            ['Mailer configured', $this->boolLabel($this->stringEnvValue('MAILER_DSN'))],
            ['Phone verification provider', $this->stringEnvValue('ACCESSING_PHONE_VERIFICATION_PROVIDER', 'not-set')],
        );

        $io->comment('This command is intentionally small and safe so the component can expose meaningful CLI diagnostics early.');

        return Command::SUCCESS;
    }

    private function boolLabel(string $value): string
    {
        return '' !== trim($value) ? 'yes' : 'no';
    }

    private function stringEnvValue(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }
}
