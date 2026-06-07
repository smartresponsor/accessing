<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe filter for user-administration audit projections.
 */
final readonly class AccessUserAdministrationAuditFilter
{
    public function __construct(
        private ?string $action = null,
        private ?string $resultStatus = null,
        private ?string $userReference = null,
        private int $limit = 50,
    ) {
    }

    public static function recent(int $limit = 50): self
    {
        return new self(limit: $limit);
    }

    public function action(): ?string
    {
        return $this->blankToNull($this->action);
    }

    public function resultStatus(): ?string
    {
        return $this->blankToNull($this->resultStatus);
    }

    public function userReference(): ?string
    {
        return $this->blankToNull($this->userReference);
    }

    public function limit(): int
    {
        return max(1, min(200, $this->limit));
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'action' => $this->action(),
            'result_status' => $this->resultStatus(),
            'user_reference' => $this->userReference(),
            'limit' => $this->limit(),
        ];
    }

    /** @return array<string, string> */
    public function criteria(): array
    {
        $criteria = [];

        if (null !== $this->action()) {
            $criteria['action'] = $this->action();
        }

        if (null !== $this->resultStatus()) {
            $criteria['resultStatus'] = $this->resultStatus();
        }

        if (null !== $this->userReference()) {
            $criteria['userReference'] = $this->userReference();
        }

        return $criteria;
    }

    private function blankToNull(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }
}
