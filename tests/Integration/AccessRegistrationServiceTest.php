<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Repository\AccessRepository;
use App\Accessing\Repository\AccessSecurityEventRepository;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;
use App\Accessing\ValueObject\AccessSecurityEventType;

final class AccessRegistrationServiceTest extends AccessDatabaseTestCase
{
    public function testRegisterRejectsDuplicateEmailBeforeDatabaseViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessRegistrationServiceInterface::class);
        /** @var AccessRepository $userRepository */
        $userRepository = static::getContainer()->get(AccessRepository::class);
        /** @var AccessSecurityEventRepository $securityEventRepository */
        $securityEventRepository = static::getContainer()->get(AccessSecurityEventRepository::class);

        $request = new AccessRegistrationRequest();
        $request->email = 'duplicate@accessing.local';
        $request->plainPassword = 'duplicate-pass-123';
        $request->displayName = 'Duplicate Tester';

        $registrationService->register($request);

        $user = $userRepository->findOneByEmailAddress('duplicate@accessing.local');
        self::assertNotNull($user);
        self::assertInstanceOf(AccessCredentialEntity::class, $user->getCredential());

        $events = $securityEventRepository->findRecentEventsForUser($user, 10);
        $registrationEvents = array_values(array_filter(
            $events,
            static fn ($event): bool => AccessSecurityEventType::UserRegistered === $event->getEventType(),
        ));

        self::assertCount(1, $registrationEvents);
        self::assertArrayNotHasKey('email', $registrationEvents[0]->getContext());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('An account already exists for duplicate@accessing.local. Sign in instead or reset the password.');

        $registrationService->register($request);
    }
}
