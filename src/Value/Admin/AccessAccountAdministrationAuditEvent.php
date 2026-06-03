<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe audit DTO for controlled Accessing account administration actions.
 */
final readonly class AccessAccountAdministrationAuditEvent
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $accountReference,
        private string $requestedBySubject,
        private string $resultStatus,
        private string $safeMessage,
        private array $safeContext = [],
    ) {
    }

    public static function fromRequestAndResult(
        AccessAccountAdministrationRequest $request,
        AccessAccountAdministrationResult $result,
    ): self {
        return new self(
            $request->action(),
            $request->accountReference(),
            $request->requestedBySubject(),
            $result->status(),
            $result->safeMessage(),
            $result->safeContext() + ['safe_reason' => $request->safeReason()],
        );
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
}
