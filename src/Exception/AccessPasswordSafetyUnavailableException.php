<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

final class AccessPasswordSafetyUnavailableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Password safety verification is temporarily unavailable.');
    }
}
