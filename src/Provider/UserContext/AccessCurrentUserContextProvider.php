<?php

declare(strict_types=1);

namespace App\Accessing\Provider\UserContext;

use App\Accessing\Context\AccessCurrentUserContext;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\ServiceInterface\UserContext\AccessCurrentUserContextProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AccessCurrentUserContextProvider implements AccessCurrentUserContextProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function current(): ?AccessCurrentUserContext
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessUserEntity || null === $user->getId()) {
            return null;
        }

        return new AccessCurrentUserContext(
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
