<?php

declare(strict_types=1);

namespace App\Accessing\Factory\SecurityEvent;

use Symfony\Component\HttpFoundation\Request;

final readonly class AccessSecurityEventContextFactory
{
    /** @return array{ipAddress: ?string, userAgent: ?string} */
    public function fromRequest(?Request $request): array
    {
        return [
            'ipAddress' => $request?->getClientIp(),
            'userAgent' => $request?->headers->get('User-Agent'),
        ];
    }
}
