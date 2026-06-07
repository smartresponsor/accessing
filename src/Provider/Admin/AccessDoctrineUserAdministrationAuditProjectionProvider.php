<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\Entity\AccessUserAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditSummary;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine-backed safe audit projection provider for Administering.
 */
final readonly class AccessDoctrineUserAdministrationAuditProjectionProvider implements AccessUserAdministrationAuditProjectionProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<AccessUserAdministrationAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return $this->matching(AccessUserAdministrationAuditFilter::recent($limit));
    }

    /** @return list<AccessUserAdministrationAuditProjection> */
    public function matching(AccessUserAdministrationAuditFilter $filter): array
    {
        $records = $this->entityManager
            ->getRepository(AccessUserAdministrationAuditRecordEntity::class)
            ->findBy($filter->criteria(), ['id' => 'DESC'], $filter->limit());

        return array_map(
            static fn (AccessUserAdministrationAuditRecordEntity $record): AccessUserAdministrationAuditProjection => new AccessUserAdministrationAuditProjection(
                $record->action(),
                $record->userReference(),
                $record->requestedBySubject(),
                $record->resultStatus(),
                $record->safeMessage(),
                $record->createdAt(),
                $record->safeContext(),
            ),
            $records,
        );
    }

    public function summary(int $limit = 200): AccessUserAdministrationAuditSummary
    {
        return $this->summarize($this->recent($limit));
    }

    public function report(AccessUserAdministrationAuditFilter $filter): AccessUserAdministrationAuditReport
    {
        $items = $this->matching($filter);

        return new AccessUserAdministrationAuditReport(
            $filter,
            $this->summarize($items),
            $items,
        );
    }

    /**
     * @param list<AccessUserAdministrationAuditProjection> $items
     */
    private function summarize(array $items): AccessUserAdministrationAuditSummary
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

        return new AccessUserAdministrationAuditSummary(
            count($items),
            $countByStatus,
            $countByAction,
            $latestAt,
        );
    }
}
