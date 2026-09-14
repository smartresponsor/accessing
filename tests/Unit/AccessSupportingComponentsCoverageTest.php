<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Provider\PhoneVerification\AccessFakePhoneVerificationProvider;
use App\Accessing\Provider\PhoneVerification\AccessNullPhoneVerificationProvider;
use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use App\Accessing\Service\PhoneVerification\AccessPhoneVerificationGatewayService;
use App\Accessing\Service\SecurityNotification\AccessSecurityNotificationService;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessPhoneNumber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AccessSupportingComponentsCoverageTest extends TestCase
{
    public function testPhoneNumberAndEmailValueObjectsNormalizeAndRejectInvalidInput(): void
    {
        $phone = new AccessPhoneNumber('+1 (713) 555-0101');
        self::assertSame('+17135550101', $phone->toString());
        self::assertSame('+17135550101', (string) $phone);

        $domestic = new AccessPhoneNumber('7135550101');
        self::assertSame('+17135550101', $domestic->toString());

        $email = new AccessEmailAddress('  Mixed.Case@Example.TEST ');
        self::assertSame('mixed.case@example.test', $email->toString());
        self::assertSame('mixed.case@example.test', (string) $email);

        try {
            new AccessPhoneNumber('not-a-number');
            self::fail('Expected an empty-digit phone number to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('A phone number is required.', $exception->getMessage());
        }

        try {
            new AccessPhoneNumber('123');
            self::fail('Expected a short phone number to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Phone numbers must contain between 10 and 15 digits.', $exception->getMessage());
        }

        try {
            new AccessPhoneNumber('+1234567890123456');
            self::fail('Expected an overlong phone number to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Phone numbers must contain between 10 and 15 digits.', $exception->getMessage());
        }
    }

    public function testPhoneVerificationProvidersAndGatewayDispatch(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Fake phone verification dispatched.', [
                'phoneNumber' => '+17135550101',
                'message' => 'Code 123456',
            ]);

        $fake = new AccessFakePhoneVerificationProvider($logger);
        self::assertTrue($fake->supports(''));
        self::assertTrue($fake->supports('fake'));
        self::assertFalse($fake->supports('null'));
        $fake->sendVerificationMessage('+17135550101', 'Code 123456');

        $null = new AccessNullPhoneVerificationProvider();
        self::assertTrue($null->supports('null'));
        self::assertFalse($null->supports('fake'));
        $null->sendVerificationMessage('+17135550101', 'ignored');

        $gateway = new AccessPhoneVerificationGatewayService([$fake, $null], 'fake');
        self::assertTrue($gateway->supports('fake'));
        self::assertFalse($gateway->supports('null'));

        $secondaryLogger = $this->createMock(LoggerInterface::class);
        $secondaryLogger->expects(self::once())->method('info');
        $secondaryFake = new AccessFakePhoneVerificationProvider($secondaryLogger);
        $dispatchGateway = new AccessPhoneVerificationGatewayService([$secondaryFake], 'fake');
        $dispatchGateway->sendVerificationMessage('+17135550101', 'Gateway code');
    }

    public function testPhoneVerificationGatewayRejectsUnsupportedProvider(): void
    {
        $provider = $this->createMock(AccessPhoneVerificationProviderInterface::class);
        $provider->method('supports')->willReturn(false);
        $gateway = new AccessPhoneVerificationGatewayService([$provider], 'missing');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No phone verification provider supports "missing".');
        $gateway->sendVerificationMessage('+17135550101', 'message');
    }

    public function testSecurityNotificationBuildsVerificationRecoveryAndResetEmails(): void
    {
        $messages = [];
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(4))
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$messages): void {
                $messages[] = $email;
            });

        $service = new AccessSecurityNotificationService($mailer, 'SmartResponsor', 'security@example.test');
        $namedUser = new AccessEntity('user@example.test', 'Oleksandr');

        $service->sendEmailVerificationCode($namedUser, '123456', 10);
        $service->sendPasswordRecoveryCode($namedUser, '654321', 15);
        $service->sendPasswordResetLink(
            $namedUser,
            'https://example.test/reset/token',
            new \DateTimeImmutable('2026-03-01T12:30:00-06:00'),
        );

        $unnamedUser = new AccessEntity('unnamed@example.test');
        $service->sendPasswordResetLink(
            $unnamedUser,
            'https://example.test/reset/other',
            new \DateTimeImmutable('2026-03-01T18:30:00+00:00'),
        );

        self::assertCount(4, $messages);
        self::assertSame('SmartResponsor email verification code', $messages[0]->getSubject());
        $verificationBody = $messages[0]->getTextBody();
        self::assertIsString($verificationBody);
        self::assertStringContainsString('123456', $verificationBody);
        self::assertSame('SmartResponsor password recovery code', $messages[1]->getSubject());
        $recoveryBody = $messages[1]->getTextBody();
        self::assertIsString($recoveryBody);
        self::assertStringContainsString('654321', $recoveryBody);
        self::assertSame('Reset your SmartResponsor password', $messages[2]->getSubject());
        $namedResetBody = $messages[2]->getTextBody();
        self::assertIsString($namedResetBody);
        self::assertStringContainsString('Hello Oleksandr,', $namedResetBody);
        self::assertStringContainsString('2026-03-01 18:30 UTC', $namedResetBody);
        $unnamedResetBody = $messages[3]->getTextBody();
        self::assertIsString($unnamedResetBody);
        self::assertStringContainsString("Hello,\n\n", $unnamedResetBody);
    }
}
