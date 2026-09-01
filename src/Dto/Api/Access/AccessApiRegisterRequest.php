<?php

declare(strict_types=1);

namespace App\Accessing\Dto\Api\Access;

final class AccessApiRegisterRequest
{
    public function __construct(
        public string $displayName = '',
        public string $email = '',
        public string $password = '',
    ) {
    }
}
