<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Repository\AccessSecurityEventRepository;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessSecurityEventRecorderTest extends AccessDatabaseTestCase
{
    public function testRecordPersistsNewAccountWithoutCascadeViolation(): void
    {
        $this->refreshDatabase();

        /** @var AccessSecurityEventRecorderInterface $recorder */
        $recorder = static::getContainer()->get(AccessSecurityEventRecorderInterface::class);
        /** @var AccessSecurityEventRepository $securityEventRepository */
        $securityEventRepository = static::getContainer()->get(AccessSecurityEventRepository::class);

        $account = new AccessAccountEntity('event-check@accessing.local', 'Event Check');

        $event = $recorder->record('integration.test_event', $account, [
            'severity' => 'info',
        ]);

        self::assertNotNull($event->getId());
        self::assertNotNull($account->getId());
        self::assertSame('event-check@accessing.local', $account->getEmail());
        self::assertSame(1, \count($securityEventRepository->findRecentEventsForAccount($account, 10)));
    }
}
