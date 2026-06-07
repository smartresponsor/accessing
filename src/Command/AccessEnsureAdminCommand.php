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

#[AsCommand(name: 'accessing:admin:ensure', description: 'Ensure the Accessing admin user exists and has ROLE_ADMIN.')]
final class AccessEnsureAdminCommand extends Command
{
    private const ADMIN_EMAIL = 'admin@smartresponsor.local';
    private const ADMIN_PASSWORD = 'admin';

    public function __construct(
        private readonly AccessRepositoryInterface $userRepository,
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

        $user = $this->userRepository->findOneByEmailAddress($email);
        $isNew = null === $user;
        $user ??= new AccessEntity();

        $user
            ->setEmail($email)
            ->setDisplayName('Accessing Admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
            ->setSecondFactorEnabled(false)
            ->unlock()
            ->resetFailedLoginCount()
            ->markEmailVerified();

        $this->credentialService->changePassword($user, self::ADMIN_PASSWORD);

        if ($dryRun) {
            $io->success(sprintf(
                'Dry run: would %s admin user %s with roles %s.',
                $isNew ? 'create' : 'update',
                $email,
                'ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH'
            ));

            return Command::SUCCESS;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            '%s admin user %s with roles %s.',
            $isNew ? 'Created' : 'Updated',
            $email,
            'ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH'
        ));

        return Command::SUCCESS;
    }
}
