<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Form\AccessAccountRegistrationType;
use App\Accessing\Form\AccessAccountSignInType;
use App\Accessing\Form\AccessChangePasswordType;
use App\Accessing\Form\AccessPasswordChangeType;
use App\Accessing\Form\AccessPhoneVerificationRequestType;
use App\Accessing\Form\AccessRecoveryRequestType;
use App\Accessing\Form\AccessRecoveryResetType;
use App\Accessing\Form\AccessResetPasswordRequestType;
use App\Accessing\Form\AccessVerificationCodeType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(AccessAccountRegistrationType::class)]
#[CoversClass(AccessAccountSignInType::class)]
#[CoversClass(AccessChangePasswordType::class)]
#[CoversClass(AccessPasswordChangeType::class)]
#[CoversClass(AccessPhoneVerificationRequestType::class)]
#[CoversClass(AccessRecoveryRequestType::class)]
#[CoversClass(AccessRecoveryResetType::class)]
#[CoversClass(AccessResetPasswordRequestType::class)]
#[CoversClass(AccessVerificationCodeType::class)]
final class AccessFormTypesTest extends TypeTestCase
{
    protected function getTypes(): array
    {
        return [
            new AccessAccountRegistrationType(),
            new AccessAccountSignInType(),
            new AccessChangePasswordType(),
            new AccessPasswordChangeType(),
            new AccessPhoneVerificationRequestType(),
            new AccessRecoveryRequestType(),
            new AccessRecoveryResetType(),
            new AccessResetPasswordRequestType(),
            new AccessVerificationCodeType(),
        ];
    }

    public function testAccountFormsExposeHelpfulFields(): void
    {
        $registration = $this->factory->create(AccessAccountRegistrationType::class);
        self::assertSame('Email address', $registration->get('email')->getConfig()->getOption('label'));
        self::assertSame('new-password', $registration->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);
        self::assertTrue($registration->has('submit'));

        $signIn = $this->factory->create(AccessAccountSignInType::class);
        self::assertSame('Email address', $signIn->get('emailAddress')->getConfig()->getOption('label'));
        self::assertSame('current-password', $signIn->get('plainPassword')->getConfig()->getOption('attr')['autocomplete']);

        $passwordChange = $this->factory->create(AccessPasswordChangeType::class);
        self::assertSame('Current password', $passwordChange->get('currentPassword')->getConfig()->getOption('label'));
        self::assertSame('New password', $passwordChange->get('newPassword')->getConfig()->getOption('label'));

        $verification = $this->factory->create(AccessVerificationCodeType::class);
        self::assertSame('Verification code', $verification->get('code')->getConfig()->getOption('label'));

        $recoveryReset = $this->factory->create(AccessRecoveryResetType::class);
        self::assertSame('Recovery code', $recoveryReset->get('code')->getConfig()->getOption('label'));
        self::assertSame('new-password', $recoveryReset->get('newPassword')->getConfig()->getOption('attr')['autocomplete']);
    }

    public function testSupportFormsKeepBusinessFriendlyHints(): void
    {
        $resetRequest = $this->factory->create(AccessResetPasswordRequestType::class);
        self::assertSame('Email address', $resetRequest->get('email')->getConfig()->getOption('label'));
        self::assertSame('Send reset link', $resetRequest->get('submit')->getConfig()->getOption('label'));

        $recoveryRequest = $this->factory->create(AccessRecoveryRequestType::class);
        self::assertSame('Email address', $recoveryRequest->get('emailAddress')->getConfig()->getOption('label'));

        $phone = $this->factory->create(AccessPhoneVerificationRequestType::class);
        self::assertSame('Phone number', $phone->get('phoneNumber')->getConfig()->getOption('label'));

        $changePassword = $this->factory->create(AccessChangePasswordType::class);
        self::assertSame('Change password', $changePassword->get('submit')->getConfig()->getOption('label'));
    }
}
