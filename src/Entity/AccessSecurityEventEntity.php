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
#[ORM\Index(name: 'idx_access_security_event_user', columns: ['user_id'])]
/**
 * Defines the security event entity type and its canonical responsibility within the Accessing component.
 */
class AccessSecurityEventEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessEntity::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AccessEntity $user;

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
        ?AccessEntity $user = null,
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

        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * Executes the get id operation within the canonical Accessing component workflow.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Executes the get user operation within the canonical Accessing component workflow.
     */
    public function getUser(): ?AccessEntity
    {
        return $this->user;
    }

    /**
     * Executes the set user operation within the canonical Accessing component workflow.
     */
    public function setUser(?AccessEntity $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Executes the get event type operation within the canonical Accessing component workflow.
     */
    public function getEventType(): AccessSecurityEventType
    {
        return AccessSecurityEventType::tryFrom($this->eventType) ?? AccessSecurityEventType::SignInFailed;
    }

    /**
     * Executes the set event type operation within the canonical Accessing component workflow.
     */
    public function setEventType(AccessSecurityEventType|string $eventType): self
    {
        if ($eventType instanceof AccessSecurityEventType) {
            $this->eventType = $eventType->value;

            return $this;
        }

        $this->eventType = trim($eventType);

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

    /**
     * Executes the get severity operation within the canonical Accessing component workflow.
     */
    public function getSeverity(): AccessSecurityEventSeverity
    {
        $severity = $this->context['severity'] ?? null;
        if (!is_string($severity)) {
            return AccessSecurityEventSeverity::Info;
        }

        $resolved = AccessSecurityEventSeverity::tryFrom($severity);
        if (!$resolved instanceof AccessSecurityEventSeverity) {
            return AccessSecurityEventSeverity::Info;
        }

        return $resolved;
    }

    /**
     * Executes the set severity operation within the canonical Accessing component workflow.
     */
    public function setSeverity(AccessSecurityEventSeverity|string $severity): self
    {
        if ($severity instanceof AccessSecurityEventSeverity) {
            $this->context['severity'] = $severity->value;

            return $this;
        }

        $this->context['severity'] = trim($severity);

        return $this;
    }

    /**
     * Executes the get ip address operation within the canonical Accessing component workflow.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Executes the set ip address operation within the canonical Accessing component workflow.
     */
    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Executes the get user agent operation within the canonical Accessing component workflow.
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Executes the set user agent operation within the canonical Accessing component workflow.
     */
    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Executes the get occurred at operation within the canonical Accessing component workflow.
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
