<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessMobileTokenPair;
use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use App\Accessing\Service\Http\Api\Access\ApiAccessFlowService;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class ApiAccessFlowServiceTest extends TestCase
{
    public function testSignInReturnsOpaqueMobileTokenPairOnSuccess(): void
    {
        $user = new AccessEntity('demo@example.test', 'Demo User');
        $user->markEmailVerified();

        $authenticationService = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authenticationService->expects(self::once())
            ->method('attemptPasswordSignIn')
            ->willReturn(AccessSignInResultDto::authenticated($user));
        $mobileTokenService = $this->createMock(AccessMobileTokenServiceInterface::class);
        $mobileTokenService->expects(self::once())
            ->method('issue')
            ->with($user, 'Test iPhone')
            ->willReturn(new AccessMobileTokenPair(
                'access-token',
                'refresh-token',
                new \DateTimeImmutable('2026-07-12T00:15:00+00:00'),
                new \DateTimeImmutable('2026-08-11T00:00:00+00:00'),
                'session-id',
            ));

        $service = new ApiAccessFlowService(
            $authenticationService,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            mobileTokenService: $mobileTokenService,
        );

        $request = Request::create(
            '/api/access/signin',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'password' => 'secret-secret',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_DEVICE_NAME' => 'Test iPhone'],
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $service->signIn($request);
        $payload = $this->decodeResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('authenticated', $payload['status']);
        self::assertSame('access-token', $payload['accessToken']);
        self::assertSame('refresh-token', $payload['refreshToken']);
        self::assertSame('2026-07-12T00:15:00+00:00', $payload['expiresAt']);
    }

    public function testRefreshRotatesOpaqueTokens(): void
    {
        $user = new AccessEntity('refresh@example.test', 'Refresh User');
        $mobileTokenService = $this->createMock(AccessMobileTokenServiceInterface::class);
        $mobileTokenService->expects(self::once())
            ->method('rotate')
            ->with('refresh-old')
            ->willReturn(new AccessMobileTokenPair(
                'access-new',
                'refresh-new',
                new \DateTimeImmutable('2026-07-12T00:15:00+00:00'),
                new \DateTimeImmutable('2026-08-11T00:00:00+00:00'),
                'session-id',
            ));
        $mobileTokenService->expects(self::once())->method('authenticate')->with('access-new')->willReturn($user);
        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            mobileTokenService: $mobileTokenService,
        );

        $response = $service->refresh(Request::create('/api/access/refresh', 'POST', content: json_encode(['refreshToken' => 'refresh-old'], JSON_THROW_ON_ERROR)));
        $payload = $this->decodeResponse($response);

        self::assertSame('access-new', $payload['accessToken']);
        self::assertSame('refresh-new', $payload['refreshToken']);
    }

    public function testBearerSessionReturnsAuthenticatedIdentity(): void
    {
        $user = new AccessEntity('bearer@example.test', 'Bearer User');
        $mobileTokenService = $this->createMock(AccessMobileTokenServiceInterface::class);
        $mobileTokenService->expects(self::once())->method('authenticate')->with('access-token')->willReturn($user);
        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            mobileTokenService: $mobileTokenService,
        );

        $request = Request::create('/api/access/session', 'GET', server: ['HTTP_AUTHORIZATION' => 'Bearer access-token']);
        $payload = $this->decodeResponse($service->session($request));

        self::assertSame('authenticated', $payload['status']);
        $identity = $payload['identity'] ?? null;
        self::assertIsArray($identity);
        self::assertSame('bearer@example.test', $identity['email'] ?? null);
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
        $payload = $this->decodeResponse($response);

        self::assertSame('verification_pending', $payload['status']);
        self::assertTrue($payload['requiresVerification']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
        $identity = $payload['identity'] ?? null;
        self::assertIsArray($identity);
        self::assertSame('demo-register@example.test', $identity['email'] ?? null);
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
        $payload = $this->decodeResponse($response);

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
        $payload = $this->decodeResponse($response);

        self::assertSame('unauthenticated', $payload['status']);
        self::assertNull($payload['identity']);
        self::assertNull($payload['accessToken']);
        self::assertNull($payload['refreshToken']);
    }

    public function testResetRecoveryUsesTypedRecoveryService(): void
    {
        $recoveryService = $this->createMock(AccessRecoveryServiceInterface::class);
        $recoveryService->expects(self::once())
            ->method('resetPassword')
            ->with('demo@example.test', '123456', 'new-secret-password')
            ->willReturn(true);

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recoveryService,
        );

        $response = $service->resetRecovery(Request::create(
            '/api/access/recovery/reset',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'code' => '123456',
                'password' => 'new-secret-password',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        ));
        $payload = $this->decodeResponse($response);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('recovery_completed', $payload['status']);
    }

    public function testResetRecoveryRejectsInvalidRecovery(): void
    {
        $recoveryService = $this->createMock(AccessRecoveryServiceInterface::class);
        $recoveryService->expects(self::once())
            ->method('resetPassword')
            ->willReturn(false);

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recoveryService,
        );

        $response = $service->resetRecovery(Request::create(
            '/api/access/recovery/reset',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'code' => 'invalid',
                'password' => 'new-secret-password',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        ));
        $payload = $this->decodeResponse($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('invalid_recovery', $payload['code']);
    }

    public function testResetRecoveryReturnsUnavailableWhenServiceIsMissing(): void
    {
        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $response = $service->resetRecovery(Request::create(
            '/api/access/recovery/reset',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'code' => '123456',
                'password' => 'new-secret-password',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        ));
        $payload = $this->decodeResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('recovery_unavailable', $payload['code']);
    }

    public function testResetRecoveryValidatesPasswordFieldBeforeCallingService(): void
    {
        $recoveryService = $this->createMock(AccessRecoveryServiceInterface::class);
        $recoveryService->expects(self::never())->method('resetPassword');

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recoveryService,
        );

        $response = $service->resetRecovery(Request::create(
            '/api/access/recovery/reset',
            'POST',
            content: json_encode([
                'email' => 'demo@example.test',
                'code' => '123456',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        ));
        $payload = $this->decodeResponse($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('invalid_request', $payload['code']);
        $fieldErrors = $payload['fieldErrors'] ?? null;
        self::assertIsArray($fieldErrors);
        self::assertSame(['The "password" field is required.'], $fieldErrors['password'] ?? null);
    }

    public function testApiAccessFlowContainsNoObfuscatedRecoveryDispatch(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/Service/Http/Api/Access/ApiAccessFlowService.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('base64_decode', $source);
        self::assertStringNotContainsString('str_rot13', $source);
        self::assertStringNotContainsString('applyAccessEngine', $source);
    }

    public function testRegisterReturnsCompromisedPasswordCode(): void
    {
        $registrationService = $this->createMock(AccessRegistrationServiceInterface::class);
        $registrationService->method('register')->willThrowException(new AccessCompromisedPasswordException());

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $response = $service->register($this->registrationRequest());
        $payload = $this->decodeResponse($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('password_compromised', $payload['code']);
    }

    public function testRegisterReturnsPasswordSafetyUnavailableCode(): void
    {
        $registrationService = $this->createMock(AccessRegistrationServiceInterface::class);
        $registrationService->method('register')->willThrowException(new AccessPasswordSafetyUnavailableException());

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
        );

        $response = $service->register($this->registrationRequest());
        $payload = $this->decodeResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('password_safety_unavailable', $payload['code']);
    }

    public function testRecoveryResetReturnsCompromisedPasswordCode(): void
    {
        $recoveryService = $this->createMock(AccessRecoveryServiceInterface::class);
        $recoveryService->method('resetPassword')->willThrowException(new AccessCompromisedPasswordException());

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recoveryService,
        );

        $response = $service->resetRecovery($this->recoveryResetRequest());
        $payload = $this->decodeResponse($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('password_compromised', $payload['code']);
    }

    public function testRecoveryResetReturnsPasswordSafetyUnavailableCode(): void
    {
        $recoveryService = $this->createMock(AccessRecoveryServiceInterface::class);
        $recoveryService->method('resetPassword')->willThrowException(new AccessPasswordSafetyUnavailableException());

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recoveryService,
        );

        $response = $service->resetRecovery($this->recoveryResetRequest());
        $payload = $this->decodeResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('password_safety_unavailable', $payload['code']);
    }

    public function testRegisterReturnsRateLimitedBeforeCallingRegistrationService(): void
    {
        $registrationService = $this->createMock(AccessRegistrationServiceInterface::class);
        $registrationService->expects(self::once())
            ->method('register')
            ->willReturn(new AccessEntity('limited@example.test', 'Limited'));
        $limiter = new RateLimiterFactory([
            'id' => 'registration_test',
            'policy' => 'sliding_window',
            'limit' => 1,
            'interval' => '60 minutes',
        ], new InMemoryStorage());

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            accessingSignUpLimiter: $limiter,
        );

        $service->register($this->registrationRequest());
        $response = $service->register($this->registrationRequest());
        $payload = $this->decodeResponse($response);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('registration_rate_limited', $payload['code']);
    }

    public function testVerificationResendReturnsStableRateLimitCode(): void
    {
        $user = new AccessEntity('resend@example.test', 'Resend');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $verificationService = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $verificationService->expects(self::once())
            ->method('resendEmailVerification')
            ->willReturn(null);

        $service = new ApiAccessFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $security,
            verificationChallengeService: $verificationService,
        );

        $response = $service->resendVerification(Request::create('/api/access/verification/resend', 'POST'));
        $payload = $this->decodeResponse($response);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('verification_resend_rate_limited', $payload['code']);
    }

    private function registrationRequest(): Request
    {
        return Request::create(
            '/api/access/register',
            'POST',
            content: json_encode([
                'displayName' => 'Password Safety',
                'email' => 'password-safety@example.test',
                'password' => 'candidate-password',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );
    }

    private function recoveryResetRequest(): Request
    {
        return Request::create(
            '/api/access/recovery/reset',
            'POST',
            content: json_encode([
                'email' => 'password-safety@example.test',
                'code' => '123456',
                'password' => 'candidate-password',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );
    }

    /** @return array<string, mixed> */
    private function decodeResponse(JsonResponse $response): array
    {
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        $objectPayload = [];

        foreach ($payload as $key => $value) {
            self::assertIsString($key);
            $objectPayload[$key] = $value;
        }

        return $objectPayload;
    }
}
