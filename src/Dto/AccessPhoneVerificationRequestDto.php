<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AccessPhoneVerificationRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 24)]
    public string $phoneNumber = '';
}
