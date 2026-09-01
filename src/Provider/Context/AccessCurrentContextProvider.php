<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Context;

use App\Accessing\Context\AccessCurrentContext;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\ProviderInterface\Context\AccessCurrentContextProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AccessCurrentContextProvider implements AccessCurrentContextProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function current(): ?AccessCurrentContext
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity || null === $user->getId()) {
            return null;
        }

        return new AccessCurrentContext(
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
