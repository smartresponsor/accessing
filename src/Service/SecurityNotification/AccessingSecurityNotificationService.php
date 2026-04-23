<?php

declare(strict_types=1);

namespace App\Accessing\Service\SecurityNotification;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\SecurityNotification\AccessingSecurityNotificationServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class AccessingSecurityNotificationService implements AccessingSecurityNotificationServiceInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $accessingProductName,
        private string $accessingMailerSender,
    ) {
    }

    public function sendEmailVerificationCode(AccessAccountEntity $account, string $plainCode, int $ttlMinutes): void
    {
        $this->mailer->send((new Email())
            ->from($this->accessingMailerSender)
            ->to($account->getEmailAddress())
            ->subject(sprintf('%s email verification code', $this->accessingProductName))
            ->text(sprintf(
                "Hello %s,\n\nYour %s email verification code is %s.\n\nThis code will expire in %d minutes.",
                $account->getDisplayName(),
                $this->accessingProductName,
                $plainCode,
                $ttlMinutes,
            )));
    }

    public function sendPasswordRecoveryCode(AccessAccountEntity $account, string $plainCode, int $ttlMinutes): void
    {
        $this->mailer->send((new Email())
            ->from($this->accessingMailerSender)
            ->to($account->getEmailAddress())
            ->subject(sprintf('%s password recovery code', $this->accessingProductName))
            ->text(sprintf(
                "Hello %s,\n\nYour %s password recovery code is %s.\n\nThis code will expire in %d minutes.",
                $account->getDisplayName(),
                $this->accessingProductName,
                $plainCode,
                $ttlMinutes,
            )));
    }
}
