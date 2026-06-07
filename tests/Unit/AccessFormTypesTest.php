<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Form\AccessChangePasswordType;
use App\Accessing\Form\AccessPasswordChangeType;
use App\Accessing\Form\AccessPhoneVerificationRequestType;
use App\Accessing\Form\AccessRecoveryRequestType;
use App\Accessing\Form\AccessRecoveryResetType;
use App\Accessing\Form\AccessResetPasswordRequestType;
use App\Accessing\Form\AccessUserRegistrationType;
use App\Accessing\Form\AccessUserSignInType;
use App\Accessing\Form\AccessVerificationCodeType;
use Symfony\Component\Form\Test\TypeTestCase;

final class AccessFormTypesTest extends TypeTestCase
{
    /** @return array<string, mixed> */
    private function fieldAttributes(string $formType, string $fieldName): array
    {
        $options = $this->factory->create($formType)->get($fieldName)->getConfig()->getOptions();
        $attributes = $options['attr'] ?? [];

        if (!is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    protected function getTypes(): array
    {
        return [
            new AccessUserRegistrationType(),
            new AccessUserSignInType(),
            new AccessChangePasswordType(),
            new AccessPasswordChangeType(),
            new AccessPhoneVerificationRequestType(),
            new AccessRecoveryRequestType(),
            new AccessRecoveryResetType(),
            new AccessResetPasswordRequestType(),
            new AccessVerificationCodeType(),
        ];
    }

    public function testUserFormsExposeHelpfulFields(): void
    {
        $registration = $this->factory->create(AccessUserRegistrationType::class);
        self::assertSame('Email address', $registration->get('email')->getConfig()->getOption('label'));
        self::assertSame('new-password', $this->fieldAttributes(AccessUserRegistrationType::class, 'plainPassword')['autocomplete'] ?? null);
        self::assertTrue($registration->has('submit'));

        $signIn = $this->factory->create(AccessUserSignInType::class);
        self::assertSame('Email address', $signIn->get('emailAddress')->getConfig()->getOption('label'));
        self::assertSame('current-password', $this->fieldAttributes(AccessUserSignInType::class, 'plainPassword')['autocomplete'] ?? null);

        $passwordChange = $this->factory->create(AccessPasswordChangeType::class);
        self::assertSame('Current password', $passwordChange->get('currentPassword')->getConfig()->getOption('label'));
        self::assertSame('New password', $passwordChange->get('newPassword')->getConfig()->getOption('label'));

        $verification = $this->factory->create(AccessVerificationCodeType::class);
        self::assertSame('Verification code', $verification->get('code')->getConfig()->getOption('label'));

        $recoveryReset = $this->factory->create(AccessRecoveryResetType::class);
        self::assertSame('Recovery code', $recoveryReset->get('code')->getConfig()->getOption('label'));
        self::assertSame('new-password', $this->fieldAttributes(AccessRecoveryResetType::class, 'newPassword')['autocomplete'] ?? null);
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
