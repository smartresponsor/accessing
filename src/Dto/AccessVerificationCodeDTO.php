<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the verification code dto type and its canonical responsibility within the Accessing component.
 */
final class AccessVerificationCodeDTO
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6,10}$/', message: 'Enter the numeric verification code.')]
    public string $code = '';
}
