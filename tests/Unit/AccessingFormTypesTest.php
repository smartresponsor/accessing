<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Form\AccountRegistrationFormType;
use App\Accessing\Form\AccountSignInFormType;
use App\Accessing\Form\ChangePasswordFormType;
use App\Accessing\Form\PasswordChangeFormType;
use App\Accessing\Form\PhoneVerificationRequestFormType;
use App\Accessing\Form\RecoveryRequestFormType;
use App\Accessing\Form\RecoveryResetFormType;
use App\Accessing\Form\ResetPasswordRequestFormType;
use App\Accessing\Form\VerificationCodeFormType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(AccountRegistrationFormType::class)]
#[CoversClass(AccountSignInFormType::class)]
#[CoversClass(ChangePasswordFormType::class)]
#[CoversClass(PasswordChangeFormType::class)]
#[CoversClass(PhoneVerificationRequestFormType::class)]
#[CoversClass(RecoveryRequestFormType::class)]
#[CoversClass(RecoveryResetFormType::class)]
#[CoversClass(ResetPasswordRequestFormType::class)]
#[CoversClass(VerificationCodeFormType::class)]
final class AccessingFormTypesTest extends TypeTestCase
{
    protected function getTypes(): array
    {
        return [
            new AccountRegistrationFormType(),
            new AccountSignInFormType(),
            new ChangePasswordFormType(),
            new PasswordChangeFormType(),
            new PhoneVerificationRequestFormType(),
            new RecoveryRequestFormType(),
            new RecoveryResetFormType(),
            new ResetPasswordRequestFormType(),
            new VerificationCodeFormType(),
        ];
    }

    public function testAccountFormsExposeHelpfulFields(): void
    {
        $registration = $this->factory->create(AccountRegistrationFormType::class);
        self::assertSame('Email address', $registration->get('email')->getConfig()->getOption('label'));
        self::assertSame('new-password', $registration->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);
        self::assertTrue($registration->has('submit'));

        $signIn = $this->factory->create(AccountSignInFormType::class);
        self::assertSame('Email address', $signIn->get('emailAddress')->getConfig()->getOption('label'));
        self::assertSame('current-password', $signIn->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);

        $passwordChange = $this->factory->create(PasswordChangeFormType::class);
        self::assertSame('Current password', $passwordChange->get('currentPassword')->getConfig()->getOption('label'));
        self::assertSame('New password', $passwordChange->get('newPassword')->getConfig()->getOption('label'));

        $verification = $this->factory->create(VerificationCodeFormType::class);
        self::assertSame('Verification code', $verification->get('code')->getConfig()->getOption('label'));

        $recoveryReset = $this->factory->create(RecoveryResetFormType::class);
        self::assertSame('Recovery code', $recoveryReset->get('code')->getConfig()->getOption('label'));
        self::assertSame('new-password', $recoveryReset->get('newPassword')->getConfig()->getOption('attr')['autocomplete']);
    }

    public function testSupportFormsKeepBusinessFriendlyHints(): void
    {
        $resetRequest = $this->factory->create(ResetPasswordRequestFormType::class);
        self::assertSame('Email address', $resetRequest->get('email')->getConfig()->getOption('label'));
        self::assertSame('Send reset link', $resetRequest->get('submit')->getConfig()->getOption('label'));

        $recoveryRequest = $this->factory->create(RecoveryRequestFormType::class);
        self::assertSame('Email address', $recoveryRequest->get('emailAddress')->getConfig()->getOption('label'));

        $phone = $this->factory->create(PhoneVerificationRequestFormType::class);
        self::assertSame('Phone number', $phone->get('phoneNumber')->getConfig()->getOption('label'));

        $changePassword = $this->factory->create(ChangePasswordFormType::class);
        self::assertSame('Change password', $changePassword->get('submit')->getConfig()->getOption('label'));
    }
}
