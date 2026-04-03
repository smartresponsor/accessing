<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RecoveryResetDto
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
