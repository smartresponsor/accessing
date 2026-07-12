<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\Api\Access\ApiAccessIdentityPayload;
use App\Accessing\Dto\Api\Access\ApiAccessSessionPayload;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use PHPUnit\Framework\TestCase;

final class ApiAccessJsonResponderTest extends TestCase
{
    public function testItEmitsCanonicalJsonForSessionPayload(): void
    {
        $responder = new ApiAccessJsonResponder();
        $response = $responder->session(
            new ApiAccessSessionPayload(
                'authenticated',
                new ApiAccessIdentityPayload('7', 'Demo', 'demo@example.test', true, true),
            ),
            200,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            [
                'status' => 'authenticated',
                'identity' => [
                    'userId' => '7',
                    'displayName' => 'Demo',
                    'email' => 'demo@example.test',
                    'emailVerified' => true,
                    'secondFactorEnabled' => true,
                ],
                'accessToken' => null,
                'refreshToken' => null,
                'expiresAt' => null,
                'pendingToken' => null,
                'requiresVerification' => false,
                'requiresSecondFactor' => false,
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
