<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

final class AccessPasskeyVerificationException extends \RuntimeException
{
    public const string ERROR_CODE = 'passkey_verification_failed';

    public function __construct()
    {
        parent::__construct('Passkey verification failed.');
    }
}
