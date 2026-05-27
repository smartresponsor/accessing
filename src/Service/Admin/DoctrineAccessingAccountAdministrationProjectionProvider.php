<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Repository\AccountRepository;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationProjection;

/**
 * Doctrine-backed safe account projection provider for Administering.
 */
final class DoctrineAccessingAccountAdministrationProjectionProvider implements AccessingAccountAdministrationProjectionProviderInterface
{
    public function __construct(private readonly AccountRepository $accountRepository)
    {
    }

    /** @return list<AccessingAccountAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return array_map(
            $this->map(...),
            $this->accountRepository->findRecentAccounts(max(1, min(100, $limit))),
        );
    }

    public function findBySubjectId(string $subjectId): ?AccessingAccountAdministrationProjection
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

    private function map(AccessAccountEntity $account): AccessingAccountAdministrationProjection
    {
        $id = $account->getId();
        $subjectId = null !== $id ? sprintf('accessing:account:%d', $id) : 'accessing:account:unpersisted';

        return new AccessingAccountAdministrationProjection(
            $subjectId,
            $account->getUserIdentifier(),
            !$account->isLocked(),
            $account->isEmailVerified(),
            $account->getRoles(),
            $account->getDisplayName(),
        );
    }
}
