<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the recovery reset dto type and its canonical responsibility within the Accessing component.
 */
final class AccessRecoveryResetDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $emailAddress = '';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6,10}$/', message: 'Enter the numeric recovery code.')]
    public string $code = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 4096)]
    public string $newPassword = '';
}
