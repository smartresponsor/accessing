<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the password change dto type and its canonical responsibility within the Accessing component.
 */
final class AccessPasswordChangeDTO
{
    #[Assert\NotBlank]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 4096)]
    public string $newPassword = '';
}
