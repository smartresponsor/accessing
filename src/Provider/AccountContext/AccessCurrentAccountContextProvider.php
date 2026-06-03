<?php

declare(strict_types=1);

namespace App\Accessing\Provider\AccountContext;

use App\Accessing\Context\AccessCurrentAccountContext;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\AccountContext\AccessCurrentAccountContextProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AccessCurrentAccountContextProvider implements AccessCurrentAccountContextProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function current(): ?AccessCurrentAccountContext
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessAccountEntity || null === $user->getId()) {
            return null;
        }

        return new AccessCurrentAccountContext(
            $user->getId(),
            $user->getUserIdentifier(),
            $user->getDisplayName(),
            array_values(array_unique($user->getRoles())),
            $user->isLocked(),
            $user->isEmailVerified(),
            $user->isSecondFactorEnabled(),
        );
    }
}
