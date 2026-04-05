<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Kernel;
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
