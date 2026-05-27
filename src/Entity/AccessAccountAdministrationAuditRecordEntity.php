<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Safe persistent audit record for controlled account administration actions.
 *
 * This entity stores metadata only. Password hashes, TOTP secrets, recovery
 * codes, reset tokens, and raw session payloads are forbidden here.
 */
#[ORM\Entity]
#[ORM\Table(name: 'accessing_account_administration_audit')]
#[ORM\Index(name: 'idx_accessing_account_admin_audit_account', columns: ['account_reference'])]
#[ORM\Index(name: 'idx_accessing_account_admin_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_accessing_account_admin_audit_requested_by', columns: ['requested_by_subject'])]
final class AccessAccountAdministrationAuditRecordEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'action', type: 'string', length: 120)]
    private string $action;

    #[ORM\Column(name: 'account_reference', type: 'string', length: 180)]
    private string $accountReference;

    #[ORM\Column(name: 'requested_by_subject', type: 'string', length: 180)]
    private string $requestedBySubject;

    #[ORM\Column(name: 'result_status', type: 'string', length: 40)]
    private string $resultStatus;

    #[ORM\Column(name: 'safe_message', type: 'text')]
    private string $safeMessage;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_context', type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(
        string $action,
        string $accountReference,
        string $requestedBySubject,
        string $resultStatus,
        string $safeMessage,
        array $safeContext = [],
    ) {
        $this->action = $action;
        $this->accountReference = $accountReference;
        $this->requestedBySubject = $requestedBySubject;
        $this->resultStatus = $resultStatus;
        $this->safeMessage = $safeMessage;
        $this->safeContext = $safeContext;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function accountReference(): string
    {
        return $this->accountReference;
    }

    public function requestedBySubject(): string
    {
        return $this->requestedBySubject;
    }

    public function resultStatus(): string
    {
        return $this->resultStatus;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getAction(): string
    {
        return $this->action();
    }

    public function getAccountReference(): string
    {
        return $this->accountReference();
    }

    public function getRequestedBySubject(): string
    {
        return $this->requestedBySubject();
    }

    public function getResultStatus(): string
    {
        return $this->resultStatus();
    }

    public function getSafeMessage(): string
    {
        return $this->safeMessage();
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
