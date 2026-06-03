<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccessAccountRegistrationRequest;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Repository\AccessAccountRepository;
use App\Accessing\ServiceInterface\Account\AccessAccountRegistrationServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessAccountRegistrationServiceTest extends AccessDatabaseTestCase
{
    public function testRegisterRejectsDuplicateEmailBeforeDatabaseViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessAccountRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessAccountRegistrationServiceInterface::class);
        /** @var AccessAccountRepository $accountRepository */
        $accountRepository = static::getContainer()->get(AccessAccountRepository::class);

        $request = new AccessAccountRegistrationRequest();
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
