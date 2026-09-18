<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Service\AccessAuthenticationService;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AccessAuthenticationServiceCoverageTest extends TestCase
{
    public function testApiCompletionMethodsUseProgrammaticSignInWithoutSecurityLogin(): void
    {
        $user = new AccessEntity('api-completion@example.test');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->expects(self::exactly(6))->method('save');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::exactly(3))->method('record')->with(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $user,
            self::isInstanceOf(Request::class),
            ['transport' => 'mobile_token'],
            false,
        );
        $security = $this->createMock(Security::class);
        $security->expects(self::never())->method('login');
        $service = $this->service($repository, $events, $security);

        foreach (['completePendingSecondFactor', 'completePasskeySignIn', 'completeExternalSignIn'] as $method) {
            $session = $this->createMock(SessionInterface::class);
            $session->expects(self::once())->method('migrate')->with(true);
            $session->expects(self::once())->method('remove')->with(AccessAuthenticationService::PENDING_SECOND_FACTOR_SESSION_KEY);
            $request = Request::create('/api/access/session');
            $request->setSession($session);
            $service->{$method}($user, $request);
        }

        self::assertSame(0, $user->getFailedSignInCount());
        self::assertNotNull($user->getLastSignInAt());
    }

    public function testCompleteMobileSecondFactorUpdatesUserAndRecordsTransport(): void
    {
        $user = new AccessEntity('mobile-completion@example.test');
        $user->registerFailedSignInAttempt()->lockUntil(new \DateTimeImmutable('+10 minutes'));
        $request = Request::create('/api/access/second-factor');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->expects(self::once())->method('save')->with($user, true);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['transport' => 'mobile_token'],
        );

        $this->service($repository, $events)->completeMobileSecondFactor($user, $request);
        self::assertSame(0, $user->getFailedSignInCount());
        self::assertFalse($user->isLocked());
        self::assertNotNull($user->getLastSignInAt());
    }

    public function testSignOutInvalidatesCurrentUserSessionAndClearsSecurityState(): void
    {
        $user = new AccessEntity('signout@example.test');
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('signout-session');
        $session->expects(self::once())->method('remove')->with(AccessAuthenticationService::PENDING_SECOND_FACTOR_SESSION_KEY);
        $session->expects(self::once())->method('invalidate');
        $request = Request::create('/access/signout');
        $request->setSession($session);

        $sessionService = $this->createMock(AccessSessionServiceInterface::class);
        $sessionService->expects(self::once())->method('invalidateCurrentSession')->with($user, $session);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SessionInvalidated,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['sessionIdentifier' => 'signout-session'],
        );
        $tokens = $this->createMock(TokenStorageInterface::class);
        $tokens->expects(self::once())->method('setToken')->with(null);

        $this->service(
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            userSessionService: $sessionService,
            tokenStorage: $tokens,
        )->signOut($user, $request);
    }

    public function testSignOutWithoutUserStillClearsSessionAndToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())->method('remove')->with(AccessAuthenticationService::PENDING_SECOND_FACTOR_SESSION_KEY);
        $session->expects(self::once())->method('invalidate');
        $request = Request::create('/access/signout');
        $request->setSession($session);
        $sessionService = $this->createMock(AccessSessionServiceInterface::class);
        $sessionService->expects(self::never())->method('invalidateCurrentSession');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::never())->method('record');
        $tokens = $this->createMock(TokenStorageInterface::class);
        $tokens->expects(self::once())->method('setToken')->with(null);

        $this->service(
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            userSessionService: $sessionService,
            tokenStorage: $tokens,
        )->signOut(null, $request);
    }

    public function testPendingSecondFactorSessionAccessorsAreStrictlyTyped(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::exactly(2))
            ->method('get')
            ->with(AccessAuthenticationService::PENDING_SECOND_FACTOR_SESSION_KEY)
            ->willReturnOnConsecutiveCalls(42, '42');
        $session->expects(self::once())->method('remove')->with(AccessAuthenticationService::PENDING_SECOND_FACTOR_SESSION_KEY);
        $service = $this->service(
            $this->createMock(AccessRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        self::assertSame(42, $service->getPendingSecondFactorUserId($session));
        self::assertNull($service->getPendingSecondFactorUserId($session));
        $service->clearPendingSecondFactor($session);
    }

    private function service(
        AccessRepositoryInterface $repository,
        AccessSecurityEventServiceInterface $events,
        ?Security $security = null,
        ?AccessSessionServiceInterface $userSessionService = null,
        ?TokenStorageInterface $tokenStorage = null,
    ): AccessAuthenticationService {
        return new AccessAuthenticationService(
            $repository,
            $this->createMock(AccessCredentialServiceInterface::class),
            $events,
            $userSessionService ?? $this->createMock(AccessSessionServiceInterface::class),
            $security ?? $this->createMock(Security::class),
            new RequestStack(),
            $tokenStorage ?? $this->createMock(TokenStorageInterface::class),
            new RateLimiterFactory([
                'id' => 'authentication_coverage',
                'policy' => 'fixed_window',
                'limit' => 10,
                'interval' => '1 minute',
            ], new InMemoryStorage()),
            5,
            15,
        );
    }
}
