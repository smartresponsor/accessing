<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function testPhoneNumberNormalizesDigits(): void
    {
        $phoneNumber = new PhoneNumber('(312) 555-0101');

        self::assertSame('+13125550101', $phoneNumber->toString());
    }
}
