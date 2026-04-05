<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AccountRegistrationRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $plainPassword = '';

    #[Assert\Length(max: 255)]
    public ?string $displayName = null;

    #[Assert\Length(max: 32)]
    public ?string $phoneNumber = null;
}
