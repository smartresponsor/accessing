<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Form\AccountRegistrationType;
use App\Accessing\Form\AccountSignInType;
use App\Accessing\Form\ChangePasswordType;
use App\Accessing\Form\PasswordChangeType;
use App\Accessing\Form\PhoneVerificationRequestType;
use App\Accessing\Form\RecoveryRequestType;
use App\Accessing\Form\RecoveryResetType;
use App\Accessing\Form\ResetPasswordRequestType;
use App\Accessing\Form\VerificationCodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(AccountRegistrationType::class)]
#[CoversClass(AccountSignInType::class)]
#[CoversClass(ChangePasswordType::class)]
#[CoversClass(PasswordChangeType::class)]
#[CoversClass(PhoneVerificationRequestType::class)]
#[CoversClass(RecoveryRequestType::class)]
#[CoversClass(RecoveryResetType::class)]
#[CoversClass(ResetPasswordRequestType::class)]
#[CoversClass(VerificationCodeType::class)]
final class AccessingFormTypesTest extends TypeTestCase
{
    protected function getTypes(): array
    {
        return [
            new AccountRegistrationType(),
            new AccountSignInType(),
            new ChangePasswordType(),
            new PasswordChangeType(),
            new PhoneVerificationRequestType(),
            new RecoveryRequestType(),
            new RecoveryResetType(),
            new ResetPasswordRequestType(),
            new VerificationCodeType(),
        ];
    }

    public function testAccountFormsExposeHelpfulFields(): void
    {
        $registration = $this->factory->create(AccountRegistrationType::class);
        self::assertSame('Email address', $registration->get('email')->getConfig()->getOption('label'));
        self::assertSame('new-password', $registration->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);
        self::assertTrue($registration->has('submit'));

        $signIn = $this->factory->create(AccountSignInType::class);
        self::assertSame('Email address', $signIn->get('emailAddress')->getConfig()->getOption('label'));
        self::assertSame('current-password', $signIn->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);

        $passwordChange = $this->factory->create(PasswordChangeType::class);
        self::assertSame('Current password', $passwordChange->get('currentPassword')->getConfig()->getOption('label'));
        self::assertSame('New password', $passwordChange->get('newPassword')->getConfig()->getOption('label'));

        $verification = $this->factory->create(VerificationCodeType::class);
        self::assertSame('Verification code', $verification->get('code')->getConfig()->getOption('label'));

        $recoveryReset = $this->factory->create(RecoveryResetType::class);
        self::assertSame('Recovery code', $recoveryReset->get('code')->getConfig()->getOption('label'));
        self::assertSame('new-password', $recoveryReset->get('newPassword')->getConfig()->getOption('attr')['autocomplete']);
    }

    public function testSupportFormsKeepBusinessFriendlyHints(): void
    {
        $resetRequest = $this->factory->create(ResetPasswordRequestType::class);
        self::assertSame('Email address', $resetRequest->get('email')->getConfig()->getOption('label'));
        self::assertSame('Send reset link', $resetRequest->get('submit')->getConfig()->getOption('label'));

        $recoveryRequest = $this->factory->create(RecoveryRequestType::class);
        self::assertSame('Email address', $recoveryRequest->get('emailAddress')->getConfig()->getOption('label'));

        $phone = $this->factory->create(PhoneVerificationRequestType::class);
        self::assertSame('Phone number', $phone->get('phoneNumber')->getConfig()->getOption('label'));

        $changePassword = $this->factory->create(ChangePasswordType::class);
        self::assertSame('Change password', $changePassword->get('submit')->getConfig()->getOption('label'));
    }
}
