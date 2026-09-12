<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

/**
 * Defines the password safety unavailable exception type and its canonical responsibility within the Accessing component.
 */
final class AccessPasswordSafetyUnavailableException extends \RuntimeException
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct()
    {
        parent::__construct('Password safety verification is temporarily unavailable.');
    }
}
