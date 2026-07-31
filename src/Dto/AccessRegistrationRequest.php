<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class AccessRegistrationRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank(message: 'Enter a password.')]
    #[Assert\Length(min: 10, max: 255, minMessage: 'Use at least {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/[a-z]/', message: 'Add a lowercase letter.')]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Add an uppercase letter.')]
    #[Assert\Regex(pattern: '/\d/', message: 'Add a number.')]
    #[Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Add a symbol.')]
    public string $plainPassword = '';

    #[Assert\Length(max: 255)]
    public ?string $displayName = null;

    #[Assert\Length(max: 32)]
    #[Assert\Regex(
        pattern: '/^$|^\+?[1-9](?:[\s().-]*\d){7,14}$/',
        message: 'Enter a valid international phone number, for example +1 555 123 4567.',
    )]
    public ?string $phoneNumber = null;

    #[Assert\Callback]
    public function validatePasswordDiffersFromEmail(ExecutionContextInterface $context): void
    {
        if ('' === trim($this->email) || '' === $this->plainPassword) {
            return;
        }

        if (0 === strcasecmp(trim($this->email), $this->plainPassword)) {
            $context->buildViolation('Password must not be identical to the email address.')
                ->atPath('plainPassword')
                ->addViolation();
        }
    }
}
