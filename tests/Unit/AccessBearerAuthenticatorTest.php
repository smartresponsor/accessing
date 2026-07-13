<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Authenticator\AccessBearerAuthenticator;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class AccessBearerAuthenticatorTest extends TestCase
{
    public function testSupportsAndCapturesBearerToken(): void
    {
        $authenticator = new AccessBearerAuthenticator($this->createMock(AccessMobileTokenServiceInterface::class));
        $request = Request::create('/api/access/session', server: [
            'HTTP_AUTHORIZATION' => 'Bearer opaque-access-token',
        ]);

        self::assertTrue($authenticator->supports($request));
        self::assertInstanceOf(SelfValidatingPassport::class, $authenticator->authenticate($request));
        self::assertSame(
            'opaque-access-token',
            $request->attributes->get(AccessBearerAuthenticator::REQUEST_ATTRIBUTE),
        );
    }

    public function testIgnoresRequestsWithoutBearerToken(): void
    {
        $authenticator = new AccessBearerAuthenticator($this->createMock(AccessMobileTokenServiceInterface::class));

        self::assertFalse($authenticator->supports(Request::create('/api/access/session')));
    }

    public function testFailureResponseIsStableAndRedacted(): void
    {
        $authenticator = new AccessBearerAuthenticator($this->createMock(AccessMobileTokenServiceInterface::class));
        $response = $authenticator->onAuthenticationFailure(Request::create('/api/access/session'), new AuthenticationException());

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('invalid_access_token', (string) $response->getContent());
    }
}
