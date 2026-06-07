<?php

declare(strict_types=1);

namespace App\Accessing\Recorder\Admin;

use App\Accessing\Entity\AccessUserAdministrationAuditRecordEntity;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AccessDoctrineUserAdministrationAuditRecorder implements AccessUserAdministrationAuditRecorderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function record(AccessUserAdministrationAuditEvent $event): void
    {
        $record = new AccessUserAdministrationAuditRecordEntity(
            $event->action(),
            $event->userReference(),
            $event->requestedBySubject(),
            $event->resultStatus(),
            $event->safeMessage(),
            $event->safeContext(),
        );

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
