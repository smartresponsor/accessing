<?php

declare(strict_types=1);

namespace App\Accessing\Clock;

use Psr\Clock\ClockInterface;

/**
 * Defines the system clock type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSystemClock implements ClockInterface
{
    /**
     * Executes the now operation within the canonical Accessing component workflow.
     */
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
