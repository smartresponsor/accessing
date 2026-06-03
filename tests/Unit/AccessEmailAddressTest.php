<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\ValueObject\AccessEmailAddress;
use PHPUnit\Framework\TestCase;

final class AccessEmailAddressTest extends TestCase
{
    public function testEmailAddressIsNormalizedToLowerCase(): void
    {
        $emailAddress = new AccessEmailAddress('  Example@Accessing.Local ');

        self::assertSame('example@accessing.local', $emailAddress->toString());
    }
}
