<?php

declare(strict_types=1);

namespace App\Accessing\Clock;

use Psr\Clock\ClockInterface;

final readonly class AccessSystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
