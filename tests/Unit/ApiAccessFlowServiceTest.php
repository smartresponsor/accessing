<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use App\Accessing\Service\Http\Api\Access\ApiAccessFlowService;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ApiAccessFlowServiceTest extends TestCase
{
    public function testSignInReturnsHonestPayloadWithoutTokensOnSuccess(): void
    {
        $user = new AccessEntity('demo@example.test', 'Demo User');
        $user->markEmailVerified();

        $authenticationService = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authenticationService->expects(self::once())
            ->method('attemptPasswordSignIn')
            ->willReturn(AccessSignInResultDto::authenticated($user));

        $service = new ApiAccessFlowService(
            $authenticationService,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $request = Request::create(
            '/api/access/signin',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'password' => 'secret-secret',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $service->signIn($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('session_transport_pending', $payload['status']);
        self::assertNull($payload['identity']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
    }

    public function testRegisterReturnsHonestPayloadWithoutTokens(): void
    {
        $user = new AccessEntity('demo-register@example.test', 'Demo Register');
        $user->markEmailVerified();

        $registrationService = $this->createMock(AccessRegistrationServiceInterface::class);
        $registrationService->expects(self::once())
            ->method('register')
            ->willReturn($user);

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $request = Request::create(
            '/api/access/register',
            'POST',
            content: json_encode([
                'displayName' => 'Demo Register',
                'email' => 'demo-register@example.test',
                'password' => 'secret-secret',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $service->register($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('verification_pending', $payload['status']);
        self::assertTrue($payload['requiresVerification']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
        self::assertSame('demo-register@example.test', $payload['identity']['email']);
    }

    public function testSessionReturnsUnauthenticatedPayloadWhenNoCurrentContextExists(): void
    {
        $contextProvider = $this->createMock(AccessCurrentContextProviderInterface::class);
        $contextProvider->expects(self::once())
            ->method('current')
            ->willReturn(null);

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $contextProvider,
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $response = $service->session(Request::create('/api/access/session', 'GET'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('unauthenticated', $payload['status']);
        self::assertNull($payload['identity']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
    }

    public function testLogoutReturnsUnauthenticatedPayloadWithoutTokens(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $authenticationService = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authenticationService->expects(self::once())
            ->method('signOut')
            ->with(null, self::isInstanceOf(Request::class));

        $service = new ApiAccessFlowService(
            $authenticationService,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $security,
        );

        $request = Request::create('/api/access/logout', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $service->logout($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('unauthenticated', $payload['status']);
        self::assertNull($payload['identity']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
    }
}
