<?php

declare(strict_types=1);

namespace App\Service\AccountSession;

use App\Entity\Account;
use App\Entity\AccountSession;
use App\ServiceInterface\AccountSession\AccessingAccountSessionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class AccessingAccountSessionManager implements AccessingAccountSessionManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function create(Account $account, ?string $sessionIdentifier = null): AccountSession
    {
        $request = $this->requestStack->getCurrentRequest();
        $accountSession = (new AccountSession())
            ->setAccount($account)
            ->setSessionIdentifier($sessionIdentifier ?? bin2hex(random_bytes(32)))
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->entityManager->persist($accountSession);
        $this->entityManager->flush();

        return $accountSession;
    }

    public function revoke(AccountSession $accountSession): void
    {
        $accountSession->revoke();
        $this->entityManager->flush();
    }
}
