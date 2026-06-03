<?php

declare(strict_types=1);

namespace App\Accessing\Provider\AccountContext;

use App\Accessing\Context\AccessingCurrentAccountContext;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\AccountContext\AccessingCurrentAccountContextProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AccessingCurrentAccountContextProvider implements AccessingCurrentAccountContextProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function current(): ?AccessingCurrentAccountContext
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessAccountEntity || null === $user->getId()) {
            return null;
        }

        return new AccessingCurrentAccountContext(
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
