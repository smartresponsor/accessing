<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class VerificationCodeDto
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6,10}$/', message: 'Enter the numeric verification code.')]
    public string $code = '';
}
