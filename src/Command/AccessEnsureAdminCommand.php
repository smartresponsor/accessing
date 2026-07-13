<?php

declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:admin:ensure', description: 'Ensure the Accessing bootstrap admin identity exists.')]
final class AccessEnsureAdminCommand extends Command
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';

    public function __construct(
        private readonly AccessRepositoryInterface $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessCredentialServiceInterface $credentialService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would change without writing to the database.')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Initial or replacement administrator password.')
            ->addOption('reset-password', null, InputOption::VALUE_NONE, 'Explicitly replace the password of an existing administrator.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $resetPassword = (bool) $input->getOption('reset-password');
        $password = $this->passwordOption($input);
        $email = self::ADMIN_EMAIL;

        $user = $this->userRepository->findOneByEmailAddress($email);
        $isNew = null === $user;

        if ($isNew && null === $password) {
            $io->error('A non-empty --password value is required when creating the administrator.');

            return Command::INVALID;
        }

        if ($resetPassword && null === $password) {
            $io->error('A non-empty --password value is required with --reset-password.');

            return Command::INVALID;
        }

        if (!$isNew && null !== $password && !$resetPassword) {
            $io->error('Refusing to change an existing administrator password without --reset-password.');

            return Command::INVALID;
        }

        $user ??= new AccessEntity();
        $user
            ->setEmail($email)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN_BOOTSTRAP', 'ROLE_ALLOWED_TO_SWITCH'])
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        if ($dryRun) {
            $io->success(sprintf(
                'Dry run: would %s admin user %s%s.',
                $isNew ? 'create' : 'update',
                $email,
                $resetPassword ? ' and reset its password' : '',
            ));

            return Command::SUCCESS;
        }

        if ($isNew || $resetPassword) {
            $this->credentialService->changePassword($user, $password);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            '%s admin user %s%s.',
            $isNew ? 'Created' : 'Updated',
            $email,
            $resetPassword ? ' and reset its password' : '',
        ));

        return Command::SUCCESS;
    }

    private function passwordOption(InputInterface $input): ?string
    {
        $password = $input->getOption('password');

        if (!is_string($password)) {
            return null;
        }

        $password = trim($password);

        return '' === $password ? null : $password;
    }
}
