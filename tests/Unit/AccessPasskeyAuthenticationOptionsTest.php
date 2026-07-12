<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasskeyAuthenticationOptions;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyAuthenticationOptionsTest extends TestCase
{
    public function testSerializesUserBoundOptions(): void
    {
        $options = new AccessPasskeyAuthenticationOptions(
            'challenge',
            'example.test',
            [['type' => 'public-key', 'id' => 'credential-id', 'transports' => ['internal']]],
        );

        self::assertSame([
            'publicKey' => [
                'challenge' => 'challenge',
                'rpId' => 'example.test',
                'allowCredentials' => [['type' => 'public-key', 'id' => 'credential-id', 'transports' => ['internal']]],
                'timeout' => 300000,
                'userVerification' => 'preferred',
            ],
        ], $options->toArray());
    }

    public function testSupportsUsernamelessOptions(): void
    {
        $options = new AccessPasskeyAuthenticationOptions('challenge', 'example.test', []);

        self::assertSame([], $options->toArray()['publicKey']['allowCredentials']);
    }

    public function testRejectsEmptyChallenge(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessPasskeyAuthenticationOptions('', 'example.test', []);
    }
}
