<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

final class AccessNotificationDeliveryException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Access notification delivery is temporarily unavailable.', 0, $previous);
    }
}
