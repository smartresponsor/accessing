<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\Entity\AccessAccountAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditSummary;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine-backed safe audit projection provider for Administering.
 */
final readonly class DoctrineAccessingAccountAdministrationAuditProjectionProvider implements AccessingAccountAdministrationAuditProjectionProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<AccessingAccountAdministrationAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return $this->matching(AccessingAccountAdministrationAuditFilter::recent($limit));
    }

    /** @return list<AccessingAccountAdministrationAuditProjection> */
    public function matching(AccessingAccountAdministrationAuditFilter $filter): array
    {
        $records = $this->entityManager
            ->getRepository(AccessAccountAdministrationAuditRecordEntity::class)
            ->findBy($filter->criteria(), ['id' => 'DESC'], $filter->limit());

        return array_map(
            static fn (AccessAccountAdministrationAuditRecordEntity $record): AccessingAccountAdministrationAuditProjection => new AccessingAccountAdministrationAuditProjection(
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

    public function summary(int $limit = 200): AccessingAccountAdministrationAuditSummary
    {
        return $this->summarize($this->recent($limit));
    }

    public function report(AccessingAccountAdministrationAuditFilter $filter): AccessingAccountAdministrationAuditReport
    {
        $items = $this->matching($filter);

        return new AccessingAccountAdministrationAuditReport(
            $filter,
            $this->summarize($items),
            $items,
        );
    }

    /**
     * @param list<AccessingAccountAdministrationAuditProjection> $items
     */
    private function summarize(array $items): AccessingAccountAdministrationAuditSummary
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

        return new AccessingAccountAdministrationAuditSummary(
            count($items),
            $countByStatus,
            $countByAction,
            $latestAt,
        );
    }
}
