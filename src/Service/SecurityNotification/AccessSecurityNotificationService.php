<?php

declare(strict_types=1);

namespace App\Accessing\Service\SecurityNotification;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\ServiceInterface\SecurityNotification\AccessSecurityNotificationServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class AccessSecurityNotificationService implements AccessSecurityNotificationServiceInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $accessingProductName,
        private string $accessingMailerSender,
    ) {
    }

    public function sendEmailVerificationCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void
    {
        $this->mailer->send((new Email())
            ->from($this->accessingMailerSender)
            ->to($user->getEmailAddress())
            ->subject(sprintf('%s email verification code', $this->accessingProductName))
            ->text(sprintf(
                "Hello %s,\n\nYour %s email verification code is %s.\n\nThis code will expire in %d minutes.",
                $user->getDisplayName(),
                $this->accessingProductName,
                $plainCode,
                $ttlMinutes,
            )));
    }

    public function sendPasswordRecoveryCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void
    {
        $this->mailer->send((new Email())
            ->from($this->accessingMailerSender)
            ->to($user->getEmailAddress())
            ->subject(sprintf('%s password recovery code', $this->accessingProductName))
            ->text(sprintf(
                "Hello %s,\n\nYour %s password recovery code is %s.\n\nThis code will expire in %d minutes.",
                $user->getDisplayName(),
                $this->accessingProductName,
                $plainCode,
                $ttlMinutes,
            )));
    }

    public function sendPasswordResetLink(
        AccessEntity $user,
        string $resetUrl,
        \DateTimeImmutable $expiresAt,
    ): void {
        $displayName = trim((string) $user->getDisplayName());
        $greeting = '' !== $displayName ? sprintf('Hello %s,', $displayName) : 'Hello,';

        $this->mailer->send((new Email())
            ->from($this->accessingMailerSender)
            ->to($user->getEmailAddress())
            ->subject(sprintf('Reset your %s password', $this->accessingProductName))
            ->text(sprintf(
                "%s\n\nUse the secure link below to reset your %s password:\n\n%s\n\nThis link expires at %s UTC. If you did not request this, you can ignore this message.",
                $greeting,
                $this->accessingProductName,
                $resetUrl,
                $expiresAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i'),
            )));
    }
}
