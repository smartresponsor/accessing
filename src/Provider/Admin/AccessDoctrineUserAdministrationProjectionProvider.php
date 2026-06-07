<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Repository\AccessUserRepository;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationProjection;

/**
 * Doctrine-backed safe user projection provider for Administering.
 */
final class AccessDoctrineUserAdministrationProjectionProvider implements AccessUserAdministrationProjectionProviderInterface
{
    public function __construct(private readonly AccessUserRepository $userRepository)
    {
    }

    /** @return list<AccessUserAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return array_map(
            $this->map(...),
            $this->userRepository->findRecentUsers(max(1, min(100, $limit))),
        );
    }

    public function findBySubjectId(string $subjectId): ?AccessUserAdministrationProjection
    {
        $prefix = 'accessing:user:';
        if (!str_starts_with($subjectId, $prefix)) {
            return null;
        }

        $id = substr($subjectId, strlen($prefix));
        if (!ctype_digit($id)) {
            return null;
        }

        $user = $this->userRepository->findById((int) $id);

        return $user instanceof AccessUserEntity ? $this->map($user) : null;
    }

    private function map(AccessUserEntity $user): AccessUserAdministrationProjection
    {
        $id = $user->getId();
        $subjectId = null !== $id ? sprintf('accessing:user:%d', $id) : 'accessing:user:unpersisted';

        return new AccessUserAdministrationProjection(
            $subjectId,
            $user->getUserIdentifier(),
            !$user->isLocked(),
            $user->isEmailVerified(),
            $user->getRoles(),
            $user->getDisplayName(),
        );
    }
}
