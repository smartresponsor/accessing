<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

final class AccessCompromisedPasswordException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('This password appears in known breach data and cannot be used.');
    }
}
