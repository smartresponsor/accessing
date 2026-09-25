<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Provider\Context\AccessCurrentContextProvider;
use App\Accessing\Service\OAuth\AccessGoogleOAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccessContextAndOAuthCoverageTest extends TestCase
{
    public function testCurrentContextProviderRejectsGuestAndTransientUserThenProjectsPersistedIdentity(): void
    {
        $guestSecurity = $this->createMock(Security::class);
        $guestSecurity->method('getUser')->willReturn(null);
        self::assertNull((new AccessCurrentContextProvider($guestSecurity))->current());

        $transientSecurity = $this->createMock(Security::class);
        $transientSecurity->method('getUser')->willReturn(new AccessEntity('transient@example.test'));
        self::assertNull((new AccessCurrentContextProvider($transientSecurity))->current());

        $user = new AccessEntity('context@example.test', 'Context User');
        $id = new \ReflectionProperty(AccessEntity::class, 'id');
        $id->setValue($user, 123);
        $user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);
        $user->markEmailVerified();
        $persistedSecurity = $this->createMock(Security::class);
        $persistedSecurity->method('getUser')->willReturn($user);

        $context = (new AccessCurrentContextProvider($persistedSecurity))->current();
        self::assertNotNull($context);
        self::assertSame(123, $context->userId());
        self::assertSame('context@example.test', $context->userIdentifier());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $context->bootstrapRoles());
        self::assertTrue($context->emailVerified());
    }

    public function testGoogleAuthorizationUrlUsesRequestOriginAndStoresState(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->with('access.google_callback')->willReturn('/access/google/callback');
        $service = new AccessGoogleOAuthService($urls, true, 'client-id', 'client-secret', '');
        $request = Request::create('https://login.example.test/access/google');
        $request->setSession(new Session(new MockArraySessionStorage()));

        self::assertTrue($service->isEnabled());
        $authorizationUrl = $service->authorizationUrl($request);
        self::assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', $authorizationUrl);
        $state = $request->getSession()->get('accessing.google_oauth_state');
        self::assertIsString($state);
        self::assertNotSame('', $state);
        self::assertStringContainsString(urlencode('https://login.example.test/access/google/callback'), $authorizationUrl);
    }

    public function testGoogleAuthorizationUrlHonorsConfiguredOriginAndDisabledConfigurationFailsClosed(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturn('/access/google/callback');
        $request = Request::create('http://localhost/access/google');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $configured = new AccessGoogleOAuthService($urls, true, ' client-id ', ' client-secret ', ' https://auth.example.test/ ');
        $authorizationUrl = $configured->authorizationUrl($request);
        self::assertStringContainsString(urlencode('https://auth.example.test/access/google/callback'), $authorizationUrl);

        $disabled = new AccessGoogleOAuthService($urls, true, '', 'client-secret', '');
        self::assertFalse($disabled->isEnabled());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Google sign-in is not configured for this application.');
        $disabled->authorizationUrl($request);
    }
}
