<?php

declare(strict_types=1);

namespace App\Accessing\Dto\Api\Access;

final readonly class ApiAccessIdentityPayload
{
    public function __construct(
        public int|string|null $userId,
        public ?string $displayName,
        public ?string $email,
        public bool $emailVerified,
        public bool $secondFactorEnabled,
    ) {
    }

    /**
     * @return array{userId: int|string|null, displayName: ?string, email: ?string, emailVerified: bool, secondFactorEnabled: bool}
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'email' => $this->email,
            'emailVerified' => $this->emailVerified,
            'secondFactorEnabled' => $this->secondFactorEnabled,
        ];
    }
}
