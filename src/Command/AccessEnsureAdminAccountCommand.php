<?php

declare(strict_types=1);

namespace App\Accessing\Command;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'accessing:admin:ensure', description: 'Ensure the Accessing admin account exists and has ROLE_ADMIN.')]
final class AccessEnsureAdminAccountCommand extends Command
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';
    private const ADMIN_PASSWORD = 'admin';

    public function __construct(
        private readonly AccessAccountRepositoryInterface $accountRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessCredentialServiceInterface $credentialService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would change without writing to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $email = self::ADMIN_EMAIL;

        $account = $this->accountRepository->findOneByEmailAddress($email);
        $isNew = null === $account;
        $account ??= new AccessAccountEntity();

        $account
            ->setEmail($email)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
            ->setSecondFactorEnabled(false)
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        $this->credentialService->changePassword($account, self::ADMIN_PASSWORD);

        if ($dryRun) {
            $io->success(sprintf(
                'Dry run: would %s admin account %s with roles %s.',
                $isNew ? 'create' : 'update',
                $email,
                'ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH'
            ));

            return Command::SUCCESS;
        }

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $io->success(sprintf(
            '%s admin account %s with roles %s.',
            $isNew ? 'Created' : 'Updated',
            $email,
            'ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH'
        ));

        return Command::SUCCESS;
    }
}
