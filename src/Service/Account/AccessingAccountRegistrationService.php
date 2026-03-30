<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Dto\AccountRegistrationRequest;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessingAccountRegistrationService implements AccessingAccountRegistrationServiceInterface
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function register(AccountRegistrationRequest $request): Account
    {
        $account = (new Account())
            ->setEmail($request->email)
            ->setDisplayName($request->displayName)
            ->setPhoneNumber($request->phoneNumber)
            ->setPasswordHash($this->userPasswordHasher->hashPassword(new Account(), $request->plainPassword));

        $this->accountRepository->save($account, true);

        return $account;
    }
}
