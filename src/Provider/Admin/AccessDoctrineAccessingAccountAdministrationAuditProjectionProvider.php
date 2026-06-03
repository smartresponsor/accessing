<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\Entity\AccessAccountAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditSummary;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine-backed safe audit projection provider for Administering.
 */
final readonly class AccessDoctrineAccessingAccountAdministrationAuditProjectionProvider implements AccessAccountAdministrationAuditProjectionProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<AccessAccountAdministrationAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return $this->matching(AccessAccountAdministrationAuditFilter::recent($limit));
    }

    /** @return list<AccessAccountAdministrationAuditProjection> */
    public function matching(AccessAccountAdministrationAuditFilter $filter): array
    {
        $records = $this->entityManager
            ->getRepository(AccessAccountAdministrationAuditRecordEntity::class)
            ->findBy($filter->criteria(), ['id' => 'DESC'], $filter->limit());

        return array_map(
            static fn (AccessAccountAdministrationAuditRecordEntity $record): AccessAccountAdministrationAuditProjection => new AccessAccountAdministrationAuditProjection(
                $record->action(),
                $record->accountReference(),
                $record->requestedBySubject(),
                $record->resultStatus(),
                $record->safeMessage(),
                $record->createdAt(),
                $record->safeContext(),
            ),
            $records,
        );
    }

    public function summary(int $limit = 200): AccessAccountAdministrationAuditSummary
    {
        return $this->summarize($this->recent($limit));
    }

    public function report(AccessAccountAdministrationAuditFilter $filter): AccessAccountAdministrationAuditReport
    {
        $items = $this->matching($filter);

        return new AccessAccountAdministrationAuditReport(
            $filter,
            $this->summarize($items),
            $items,
        );
    }

    /**
     * @param list<AccessAccountAdministrationAuditProjection> $items
     */
    private function summarize(array $items): AccessAccountAdministrationAuditSummary
    {
        $countByStatus = [];
        $countByAction = [];
        $latestAt = null;

        foreach ($items as $item) {
            $countByStatus[$item->resultStatus()] = ($countByStatus[$item->resultStatus()] ?? 0) + 1;
            $countByAction[$item->action()] = ($countByAction[$item->action()] ?? 0) + 1;

            if (null === $latestAt || $item->createdAt() > $latestAt) {
                $latestAt = $item->createdAt();
            }
        }

        ksort($countByStatus);
        ksort($countByAction);

        return new AccessAccountAdministrationAuditSummary(
            count($items),
            $countByStatus,
            $countByAction,
            $latestAt,
        );
    }
}
