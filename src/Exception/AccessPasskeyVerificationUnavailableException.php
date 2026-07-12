<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

final class AccessPasskeyVerificationUnavailableException extends \RuntimeException
{
    public const string ERROR_CODE = 'passkey_verification_unavailable';

    public function __construct()
    {
        parent::__construct('Passkey verification is temporarily unavailable.');
    }
}
