<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Repository\AccessAccountRepository;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationProjection;

/**
 * Doctrine-backed safe account projection provider for Administering.
 */
final class AccessDoctrineAccountAdministrationProjectionProvider implements AccessAccountAdministrationProjectionProviderInterface
{
    public function __construct(private readonly AccessAccountRepository $accountRepository)
    {
    }

    /** @return list<AccessAccountAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return array_map(
            $this->map(...),
            $this->accountRepository->findRecentAccounts(max(1, min(100, $limit))),
        );
    }

    public function findBySubjectId(string $subjectId): ?AccessAccountAdministrationProjection
    {
        $prefix = 'accessing:account:';
        if (!str_starts_with($subjectId, $prefix)) {
            return null;
        }

        $id = substr($subjectId, strlen($prefix));
        if (!ctype_digit($id)) {
            return null;
        }

        $account = $this->accountRepository->findById((int) $id);

        return $account instanceof AccessAccountEntity ? $this->map($account) : null;
    }

    private function map(AccessAccountEntity $account): AccessAccountAdministrationProjection
    {
        $id = $account->getId();
        $subjectId = null !== $id ? sprintf('accessing:account:%d', $id) : 'accessing:account:unpersisted';

        return new AccessAccountAdministrationProjection(
            $subjectId,
            $account->getUserIdentifier(),
            !$account->isLocked(),
            $account->isEmailVerified(),
            $account->getRoles(),
            $account->getDisplayName(),
        );
    }
}
