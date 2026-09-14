<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

/**
 * Defines the notification delivery exception type and its canonical responsibility within the Accessing component.
 */
final class AccessNotificationDeliveryException extends \RuntimeException
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Access notification delivery is temporarily unavailable.', 0, $previous);
    }
}
