<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\Api\Access\AccessApiErrorDTO;
use App\Accessing\DTO\Api\Access\AccessApiIdentityDTO;
use App\Accessing\DTO\Api\Access\AccessApiRegisterRequestDTO;
use App\Accessing\DTO\Api\Access\AccessApiSessionDTO;
use App\Accessing\DTO\Api\Access\AccessApiSignInRequestDTO;
use PHPUnit\Framework\TestCase;

final class AccessApiDtoContractTest extends TestCase
{
    public function testRegisterRequestUsesDisplayNameEmailAndPasswordOnly(): void
    {
        $request = new AccessApiRegisterRequestDTO();

        self::assertObjectHasProperty('displayName', $request);
        self::assertObjectHasProperty('email', $request);
        self::assertObjectHasProperty('password', $request);
        self::assertFalse((new \ReflectionObject($request))->hasProperty('companyName'));
    }

    public function testSignInRequestUsesEmailAndPasswordOnly(): void
    {
        $request = new AccessApiSignInRequestDTO();

        self::assertObjectHasProperty('email', $request);
        self::assertObjectHasProperty('password', $request);
    }

    public function testSessionPayloadDoesNotFakeTokensOrIdentity(): void
    {
        $payload = new AccessApiSessionDTO('unauthenticated');

        self::assertNull($payload->identity);
        self::assertNull($payload->accessToken);
        self::assertNull($payload->refreshToken);
        self::assertNull($payload->expiresAt);
        self::assertNull($payload->pendingToken);
        self::assertFalse($payload->requiresVerification);
        self::assertFalse($payload->requiresSecondFactor);
        self::assertSame('unauthenticated', $payload->status);
    }

    public function testIdentityPayloadSerializesCanonicalFields(): void
    {
        $payload = new AccessApiIdentityDTO('42', 'Demo User', 'demo@example.test', true, false);

        self::assertSame(
            [
                'userId' => '42',
                'displayName' => 'Demo User',
                'email' => 'demo@example.test',
                'emailVerified' => true,
                'secondFactorEnabled' => false,
                'userUuid' => null,
            ],
            $payload->toArray(),
        );
    }

    public function testErrorPayloadSerializesFieldErrors(): void
    {
        $payload = new AccessApiErrorDTO('invalid_request', 'Bad request', ['email' => ['Required']]);

        self::assertSame(
            [
                'code' => 'invalid_request',
                'message' => 'Bad request',
                'fieldErrors' => ['email' => ['Required']],
            ],
            $payload->toArray(),
        );
    }
}
