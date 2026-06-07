<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Repository\AccessRepository;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessRegistrationServiceTest extends AccessDatabaseTestCase
{
    public function testRegisterRejectsDuplicateEmailBeforeDatabaseViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessRegistrationServiceInterface::class);
        /** @var AccessRepository $userRepository */
        $userRepository = static::getContainer()->get(AccessRepository::class);

        $request = new AccessRegistrationRequest();
        $request->email = 'duplicate@accessing.local';
        $request->plainPassword = 'duplicate-pass-123';
        $request->displayName = 'Duplicate Tester';

        $registrationService->register($request);

        $user = $userRepository->findOneByEmailAddress('duplicate@accessing.local');
        self::assertNotNull($user);
        self::assertInstanceOf(AccessCredentialEntity::class, $user?->getCredential());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('An user with email "duplicate@accessing.local" already exists.');

        $registrationService->register($request);
    }
}
