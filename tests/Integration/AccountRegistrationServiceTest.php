<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Repository\AccountRepository;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\Tests\Support\DatabaseTestCase;

final class AccountRegistrationServiceTest extends DatabaseTestCase
{
    public function testRegisterRejectsDuplicateEmailBeforeDatabaseViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessingAccountRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessingAccountRegistrationServiceInterface::class);
        /** @var AccountRepository $accountRepository */
        $accountRepository = static::getContainer()->get(AccountRepository::class);

        $request = new AccountRegistrationRequest();
        $request->email = 'duplicate@accessing.local';
        $request->plainPassword = 'duplicate-pass-123';
        $request->displayName = 'Duplicate Tester';

        $registrationService->register($request);

        $account = $accountRepository->findOneByEmailAddress('duplicate@accessing.local');
        self::assertNotNull($account);
        self::assertInstanceOf(AccessCredentialEntity::class, $account?->getCredential());

        self::expectException(\DomainException::class);
        self::expectExceptionMessage('An account with email "duplicate@accessing.local" already exists.');

        $registrationService->register($request);
    }
}
