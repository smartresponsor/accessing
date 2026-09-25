<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the phone verification request dto type and its canonical responsibility within the Accessing component.
 */
final class AccessPhoneVerificationRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 24)]
    public string $phoneNumber = '';
}
