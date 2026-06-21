<?php

declare(strict_types=1);

namespace App\Accessing\Dto\Api\Access;

final readonly class ApiAccessErrorPayload
{
    /**
     * @param array<string, list<string>>|null $fieldErrors
     */
    public function __construct(
        public string $code,
        public string $message,
        public ?array $fieldErrors = null,
    ) {
    }

    /**
     * @return array{code: string, message: string, fieldErrors: array<string, list<string>>|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'fieldErrors' => $this->fieldErrors,
        ];
    }
}
