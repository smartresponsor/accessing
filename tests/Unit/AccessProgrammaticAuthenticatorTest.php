<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Authenticator\AccessProgrammaticAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class AccessProgrammaticAuthenticatorTest extends TestCase
{
    public function testProgrammaticAuthenticatorNeverSupportsRequestAuthentication(): void
    {
        $authenticator = new AccessProgrammaticAuthenticator();

        self::assertFalse($authenticator->supports(Request::create('/access')));
    }

    public function testAuthenticateRejectsDirectUse(): void
    {
        $authenticator = new AccessProgrammaticAuthenticator();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('only used through Symfony Security::login()');
        $authenticator->authenticate(Request::create('/access'));
    }

    public function testAuthenticationCallbacksReturnNull(): void
    {
        $authenticator = new AccessProgrammaticAuthenticator();
        $request = Request::create('/access');

        self::assertNull($authenticator->onAuthenticationSuccess(
            $request,
            $this->createMock(TokenInterface::class),
            'main',
        ));
        self::assertNull($authenticator->onAuthenticationFailure(
            $request,
            new AuthenticationException(),
        ));
    }
}
