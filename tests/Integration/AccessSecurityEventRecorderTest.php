<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Repository\AccessSecurityEventRepository;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessSecurityEventRecorderTest extends AccessDatabaseTestCase
{
    public function testRecordPersistsNewUserWithoutCascadeViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessSecurityEventRecorderInterface $recorder */
        $recorder = static::getContainer()->get(AccessSecurityEventRecorderInterface::class);
        /** @var AccessSecurityEventRepository $securityEventRepository */
        $securityEventRepository = static::getContainer()->get(AccessSecurityEventRepository::class);

        $user = new AccessUserEntity('event-check@accessing.local', 'Event Check');

        $event = $recorder->record('integration.test_event', $user, [
            'severity' => 'info',
        ]);

        self::assertNotNull($event->getId());
        self::assertNotNull($user->getId());
        self::assertSame('event-check@accessing.local', $user->getEmail());
        self::assertSame(1, \count($securityEventRepository->findRecentEventsForUser($user, 10)));
    }
}
