<?php

declare(strict_types=1);

namespace App\Accessing\Service\SecurityNotification;

use App\Accessing\Entity\AccessUserEntity;
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

    public function sendEmailVerificationCode(AccessUserEntity $user, string $plainCode, int $ttlMinutes): void
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

    public function sendPasswordRecoveryCode(AccessUserEntity $user, string $plainCode, int $ttlMinutes): void
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
}
