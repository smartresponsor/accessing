<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\Entity\AccessAccountAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAccessingAccountAdministrationAuditRecorder implements AccessingAccountAdministrationAuditRecorderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function record(AccessingAccountAdministrationAuditEvent $event): void
    {
        $record = new AccessAccountAdministrationAuditRecordEntity(
            $event->action(),
            $event->accountReference(),
            $event->requestedBySubject(),
            $event->resultStatus(),
            $event->safeMessage(),
            $event->safeContext(),
        );

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
