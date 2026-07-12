<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessPasskeyRelyingPartyConfig
{
    public function __construct(public string $id, public string $name, public string $origin)
    {
        if ('' === trim($id) || '' === trim($name) || '' === trim($origin)) {
            throw new \InvalidArgumentException('Passkey relying-party configuration cannot contain empty values.');
        }

        $parts = parse_url($origin);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException('Passkey origin must be an absolute origin URL.');
        }

        if (!in_array($parts['scheme'], ['https', 'http'], true)) {
            throw new \InvalidArgumentException('Passkey origin must use HTTP or HTTPS.');
        }

        if ('http' === $parts['scheme'] && !in_array($parts['host'], ['localhost', '127.0.0.1'], true)) {
            throw new \InvalidArgumentException('Insecure passkey origins are allowed only for localhost development.');
        }
    }
}
