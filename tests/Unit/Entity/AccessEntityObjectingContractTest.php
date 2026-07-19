<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit\Entity;

use App\Accessing\Entity\AccessEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class AccessEntityObjectingContractTest extends TestCase
{
    public function testAccessEntityUsesObjectingIdentityAndAuditWithoutConsumerDuplicates(): void
    {
        $access = new AccessEntity('Owner@Example.com', 'Owner');
        $objectUuid = $access->getObjectUuid();
        $createdAt = $access->getCreatedAt();

        self::assertSame(26, \strlen($objectUuid));
        self::assertInstanceOf(UuidV7::class, Uuid::fromString($objectUuid));
        self::assertSame($objectUuid, $access->getObjectSlug());
        self::assertSame($createdAt, $access->getRegisteredAt());
        self::assertSame($createdAt, $access->getUpdatedAt());
        self::assertNull($access->getModifiedAt());
        self::assertNull($access->getId());

        $access->setDisplayName('Updated Owner');

        self::assertSame($objectUuid, $access->getObjectUuid());
        self::assertNotNull($access->getModifiedAt());
        self::assertSame($access->getModifiedAt(), $access->getUpdatedAt());
    }
}
