<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SecurityEventRepository;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SecurityEventRepository::class)]
#[ORM\Table(name: 'security_event')]
#[ORM\Index(name: 'idx_security_event_occurred_at', columns: ['occurred_at'])]
final class SecurityEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'securityEvents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Account $account;

    #[ORM\Column(enumType: SecurityEventType::class, length: 64)]
    private SecurityEventType $eventType;

    #[ORM\Column(enumType: SecurityEventSeverity::class, length: 16)]
    private SecurityEventSeverity $severity;

    #[ORM\Column(nullable: true, length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(nullable: true, length: 1000)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'json')]
    private array $context = [];

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        ?Account $account = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $context = [],
    ) {
        $this->eventType = $eventType;
        $this->severity = $severity;
        $this->account = $account;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->context = $context;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    public function getEventType(): SecurityEventType
    {
        return $this->eventType;
    }

    public function getSeverity(): SecurityEventSeverity
    {
        return $this->severity;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
