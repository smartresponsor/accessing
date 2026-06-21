<?php

declare(strict_types=1);

namespace App\Accessing\Dto\Api\Access;

final class ApiAccessSignInRequest
{
    public function __construct(
        public string $email = '',
        public string $password = '',
    ) {
    }
}
