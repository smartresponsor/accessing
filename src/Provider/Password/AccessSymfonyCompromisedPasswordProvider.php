<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Password;

use App\Accessing\Dto\AccessPasswordSafetyResult;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class AccessSymfonyCompromisedPasswordProvider implements AccessCompromisedPasswordProviderInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function check(string $plainPassword): AccessPasswordSafetyResult
    {
        try {
            $violations = $this->validator->validate(
                $plainPassword,
                new NotCompromisedPassword(skipOnError: false),
            );
        } catch (\Throwable) {
            return new AccessPasswordSafetyResult(AccessPasswordSafetyStatus::Unavailable);
        }

        return new AccessPasswordSafetyResult(
            0 === $violations->count()
                ? AccessPasswordSafetyStatus::Safe
                : AccessPasswordSafetyStatus::Compromised,
        );
    }
}
