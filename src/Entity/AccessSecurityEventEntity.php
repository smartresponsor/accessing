<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_security_event')]
#[ORM\Index(name: 'idx_access_security_event_type', columns: ['event_type'])]
#[ORM\Index(name: 'idx_access_security_event_occurred_at', columns: ['occurred_at'])]
class AccessSecurityEventEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessAccountEntity::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AccessAccountEntity $account;

    #[ORM\Column(name: 'event_type', length: 64)]
    private string $eventType = '';

    /** @var array<string, scalar|array<array-key, mixed>|null> */
    #[ORM\Column(type: Types::JSON)]
    private array $context;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    /**
     * @param array<string, scalar|array<array-key, mixed>|null> $context
     */
    public function __construct(
        AccessSecurityEventType|string|null $eventType = null,
        AccessSecurityEventSeverity|string|null $severity = null,
        ?AccessAccountEntity $account = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $context = [],
    ) {
        $this->occurredAt = new \DateTimeImmutable();
        $this->context = $context;

        if (null !== $eventType) {
            $this->setEventType($eventType);
        }

        if (null !== $severity) {
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

    public function getAccount(): ?AccessAccountEntity
    {
        return $this->account;
    }

    public function setAccount(?AccessAccountEntity $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getEventType(): AccessSecurityEventType
    {
        return AccessSecurityEventType::tryFrom($this->eventType) ?? AccessSecurityEventType::SignInFailed;
    }

    public function setEventType(AccessSecurityEventType|string $eventType): self
    {
        $this->eventType = trim($eventType instanceof AccessSecurityEventType ? $eventType->value : $eventType);

        return $this;
    }

    /** @return array<string, scalar|array<array-key, mixed>|null> */
    public function getContext(): array
    {
        return $this->context;
    }

    /** @param array<string, scalar|array<array-key, mixed>|null> $context */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getSeverity(): AccessSecurityEventSeverity
    {
        $severity = $this->context['severity'] ?? null;

        return is_string($severity) && AccessSecurityEventSeverity::tryFrom($severity) instanceof AccessSecurityEventSeverity
            ? AccessSecurityEventSeverity::from($severity)
            : AccessSecurityEventSeverity::Info;
    }

    public function setSeverity(AccessSecurityEventSeverity|string $severity): self
    {
        $this->context['severity'] = $severity instanceof AccessSecurityEventSeverity ? $severity->value : trim($severity);

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
}
