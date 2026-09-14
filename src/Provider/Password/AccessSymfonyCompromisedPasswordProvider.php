<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Password;

use App\Accessing\DTO\AccessPasswordSafetyResultDTO;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines the symfony compromised password provider type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSymfonyCompromisedPasswordProvider implements AccessCompromisedPasswordProviderInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * Executes the check operation within the canonical Accessing component workflow.
     */
    public function check(string $plainPassword): AccessPasswordSafetyResultDTO
    {
        try {
            $violations = $this->validator->validate(
                $plainPassword,
                new NotCompromisedPassword(skipOnError: false),
            );
        } catch (\Throwable) {
            return new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Unavailable);
        }

        return new AccessPasswordSafetyResultDTO(
            0 === $violations->count()
                ? AccessPasswordSafetyStatus::Safe
                : AccessPasswordSafetyStatus::Compromised,
        );
    }
}
