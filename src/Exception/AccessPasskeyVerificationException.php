<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

/**
 * Defines the passkey verification exception type and its canonical responsibility within the Accessing component.
 */
final class AccessPasskeyVerificationException extends \RuntimeException
{
    public const string ERROR_CODE = 'passkey_verification_failed';

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct()
    {
        parent::__construct('Passkey verification failed.');
    }
}
