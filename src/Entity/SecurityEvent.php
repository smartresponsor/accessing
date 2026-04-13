<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accessing_security_event')]
#[ORM\Index(name: 'idx_accessing_security_event_type', columns: ['event_type'])]
#[ORM\Index(name: 'idx_accessing_security_event_occurred_at', columns: ['occurred_at'])]
class SecurityEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Account $account = null;

    #[ORM\Column(length: 64, name: 'event_type')]
    private string $eventType = '';

    #[ORM\Column(type: Types::JSON)]
    private array $context = [];

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'occurred_at')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        SecurityEventType|string|null $eventType = null,
        SecurityEventSeverity|string|null $severity = null,
        ?Account $account = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $context = [],
    ) {
        $this->occurredAt = new \DateTimeImmutable();
        $this->context = $context;

        if ($eventType !== null) {
            $this->setEventType($eventType);
        }

        if ($severity !== null) {
            $this->setSeverity($severity);
        }

        $this->account = $account;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getEventType(): SecurityEventType
    {
        return SecurityEventType::tryFrom($this->eventType) ?? SecurityEventType::SignInFailed;
    }

    public function setEventType(SecurityEventType|string $eventType): self
    {
        $this->eventType = trim($eventType instanceof SecurityEventType ? $eventType->value : $eventType);

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getSeverity(): SecurityEventSeverity
    {
        $severity = $this->context['severity'] ?? null;

        return match (true) {
            $severity instanceof SecurityEventSeverity => $severity,
            is_string($severity) && SecurityEventSeverity::tryFrom($severity) instanceof SecurityEventSeverity => SecurityEventSeverity::from($severity),
            default => SecurityEventSeverity::Info,
        };
    }

    public function setSeverity(SecurityEventSeverity|string $severity): self
    {
        $this->context['severity'] = $severity instanceof SecurityEventSeverity ? $severity->value : trim($severity);

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }
}
