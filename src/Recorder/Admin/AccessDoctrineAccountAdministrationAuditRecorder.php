<?php

declare(strict_types=1);

namespace App\Accessing\Recorder\Admin;

use App\Accessing\Entity\AccessAccountAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AccessDoctrineAccountAdministrationAuditRecorder implements AccessAccountAdministrationAuditRecorderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function record(AccessAccountAdministrationAuditEvent $event): void
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
