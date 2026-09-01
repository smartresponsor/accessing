<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Provider\Password\AccessSymfonyCompromisedPasswordProvider;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AccessSymfonyCompromisedPasswordProviderTest extends TestCase
{
    public function testSafePasswordReturnsSafeOutcome(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with('candidate-password', self::isInstanceOf(NotCompromisedPassword::class))
            ->willReturn(new ConstraintViolationList());

        $result = (new AccessSymfonyCompromisedPasswordProvider($validator))->check('candidate-password');

        self::assertSame(AccessPasswordSafetyStatus::Safe, $result->status);
    }

    public function testViolationReturnsCompromisedOutcome(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Compromised password.', null, [], null, '', 'candidate-password'),
        ]));

        $result = (new AccessSymfonyCompromisedPasswordProvider($validator))->check('candidate-password');

        self::assertSame(AccessPasswordSafetyStatus::Compromised, $result->status);
    }

    public function testValidatorFailureReturnsUnavailableOutcome(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willThrowException(new \RuntimeException('provider unavailable'));

        $result = (new AccessSymfonyCompromisedPasswordProvider($validator))->check('candidate-password');

        self::assertSame(AccessPasswordSafetyStatus::Unavailable, $result->status);
    }
}
