<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccessKernelBootTest extends KernelTestCase
{
    public function testKernelBootsAndContainerIsAvailable(): void
    {
        self::bootKernel();

        self::assertInstanceOf(Kernel::class, self::$kernel);
        self::assertTrue(static::getContainer()->has('router'));
    }
}
