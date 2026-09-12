<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the recovery request dto type and its canonical responsibility within the Accessing component.
 */
final class AccessRecoveryRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $emailAddress = '';
}
