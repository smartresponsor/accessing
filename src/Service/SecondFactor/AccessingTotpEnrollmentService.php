<?php

declare(strict_types=1);

namespace App\Service\SecondFactor;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;

final class AccessingTotpEnrollmentService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function prepare(Account $account): Account
    {
        if (null === $account->getTotpSecret() || '' === $account->getTotpSecret()) {
            $account->setTotpSecret($this->generateSecret());
            $this->entityManager->flush();
        }

        return $account;
    }

    public function enable(Account $account): void
    {
        $this->prepare($account);
        $account->setSecondFactorEnabled(true);
        $this->entityManager->flush();
    }

    public function disable(Account $account): void
    {
        $account
            ->setSecondFactorEnabled(false)
            ->setTotpSecret(null);

        $this->entityManager->flush();
    }

    public function buildProvisioningUri(Account $account, string $issuer = 'Accessing'): string
    {
        $this->prepare($account);

        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($account->getEmail()),
            rawurlencode((string) $account->getTotpSecret()),
            rawurlencode($issuer),
        );
    }

    private function generateSecret(int $length = 32): string
    {
        $secret = '';
        $alphabetMaxIndex = strlen(self::BASE32_ALPHABET) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $secret .= self::BASE32_ALPHABET[random_int(0, $alphabetMaxIndex)];
        }

        return $secret;
    }
}
