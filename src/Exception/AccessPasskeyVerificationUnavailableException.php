<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

/**
 * Defines the passkey verification unavailable exception type and its canonical responsibility within the Accessing component.
 */
final class AccessPasskeyVerificationUnavailableException extends \RuntimeException
{
    public const string ERROR_CODE = 'passkey_verification_unavailable';

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct()
    {
        parent::__construct('Passkey verification is temporarily unavailable.');
    }
}
