<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\Api\Access\ApiAccessErrorPayload;
use App\Accessing\Dto\Api\Access\ApiAccessIdentityPayload;
use App\Accessing\Dto\Api\Access\ApiAccessRegisterRequest;
use App\Accessing\Dto\Api\Access\ApiAccessSessionPayload;
use App\Accessing\Dto\Api\Access\ApiAccessSignInRequest;
use PHPUnit\Framework\TestCase;

final class ApiAccessDtoContractTest extends TestCase
{
    public function testRegisterRequestUsesDisplayNameEmailAndPasswordOnly(): void
    {
        $request = new ApiAccessRegisterRequest();

        self::assertObjectHasProperty('displayName', $request);
        self::assertObjectHasProperty('email', $request);
        self::assertObjectHasProperty('password', $request);
        self::assertFalse((new \ReflectionObject($request))->hasProperty('companyName'));
    }

    public function testSignInRequestUsesEmailAndPasswordOnly(): void
    {
        $request = new ApiAccessSignInRequest();

        self::assertObjectHasProperty('email', $request);
        self::assertObjectHasProperty('password', $request);
    }

    public function testSessionPayloadDoesNotFakeTokensOrIdentity(): void
    {
        $payload = new ApiAccessSessionPayload('unauthenticated');

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
        $payload = new ApiAccessIdentityPayload('42', 'Demo User', 'demo@example.test', true, false);

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
        $payload = new ApiAccessErrorPayload('invalid_request', 'Bad request', ['email' => ['Required']]);

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
