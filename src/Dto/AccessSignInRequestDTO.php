<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the sign in request dto type and its canonical responsibility within the Accessing component.
 */
final class AccessSignInRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $emailAddress = '';

    #[Assert\NotBlank]
    public string $plainPassword = '';
}
