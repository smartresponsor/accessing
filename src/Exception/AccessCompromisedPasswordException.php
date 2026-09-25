<?php

declare(strict_types=1);

namespace App\Accessing\Exception;

/**
 * Defines the compromised password exception type and its canonical responsibility within the Accessing component.
 */
final class AccessCompromisedPasswordException extends \DomainException
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct()
    {
        parent::__construct('This password appears in known breach data and cannot be used.');
    }
}
