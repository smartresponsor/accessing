<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelBootTest extends KernelTestCase
{
    public function testKernelBootsAndContainerIsAvailable(): void
    {
        self::bootKernel();

        self::assertInstanceOf(Kernel::class, self::$kernel);
        self::assertNotNull(static::getContainer()->get('router'));
    }
}
