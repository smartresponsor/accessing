<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\AccessMobilePendingTokenDTO;
use App\Accessing\DTO\AccessMobileTokenPairDTO;
use App\Accessing\DTO\AccessSignInResultDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\ProviderInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\Responder\Api\Access\AccessApiJsonResponder;
use App\Accessing\Service\Http\Api\Access\AccessApiFlowService;
use App\Accessing\ServiceInterface\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobilePendingAuthServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AccessApiFlowServiceTest extends TestCase
{
    public function testSignInReturnsOpaqueMobileTokenPairOnSuccess(): void
    {
        $user = new AccessEntity('demo@example.test', 'Demo User');
        $user->markEmailVerified();

        $authenticationService = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authenticationService->expects(self::once())
            ->method('attemptPasswordSignIn')
            ->willReturn(AccessSignInResultDTO::authenticated($user));
        $mobileTokenService = $this->createMock(AccessMobileTokenServiceInterface::class);
        $mobileTokenService->expects(self::once())
            ->method('issue')
            ->with($user, 'Test iPhone')
            ->willReturn(new AccessMobileTokenPairDTO(
                'access-token',
                'refresh-token',
                new \DateTimeImmutable('2026-07-12T00:15:00+00:00'),
                new \DateTimeImmutable('2026-08-11T00:00:00+00:00'),
                'session-id',
            ));

        $service = new AccessApiFlowService(
            $authenticationService,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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
            ->willReturn(new AccessMobileTokenPairDTO(
                'access-new',
                'refresh-new',
                new \DateTimeImmutable('2026-07-12T00:15:00+00:00'),
                new \DateTimeImmutable('2026-08-11T00:00:00+00:00'),
                'session-id',
            ));
        $mobileTokenService->expects(self::once())->method('authenticate')->with('access-new')->willReturn($user);
        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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
        $security = $this->createMock(Security::class);
        $security->expects(self::once())->method('getUser')->willReturn($user);
        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
        );

        $request = Request::create('/api/access/session', 'GET');
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
        $pendingAuthService = $this->createMock(AccessMobilePendingAuthServiceInterface::class);
        $pendingAuthService->expects(self::once())
            ->method('issue')
            ->with($user, AccessMobilePendingPurpose::EmailVerification, 'Symfony')
            ->willReturn(new AccessMobilePendingTokenDTO('pending-token', new \DateTimeImmutable('2026-07-12T00:10:00+00:00')));

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            mobilePendingAuthService: $pendingAuthService,
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
        self::assertSame('pending-token', $payload['pendingToken']);
        self::assertSame('2026-07-12T00:10:00+00:00', $payload['expiresAt']);
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $contextProvider,
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $authenticationService,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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
        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

    public function testAccessApiFlowContainsNoObfuscatedRecoveryDispatch(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/Service/Http/Api/Access/AccessApiFlowService.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('base64_decode', $source);
        self::assertStringNotContainsString('str_rot13', $source);
        self::assertStringNotContainsString('applyAccessEngine', $source);
    }

    public function testRegisterReturnsCompromisedPasswordCode(): void
    {
        $registrationService = $this->createMock(AccessRegistrationServiceInterface::class);
        $registrationService->method('register')->willThrowException(new AccessCompromisedPasswordException());

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $registrationService,
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
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

        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
            verificationChallengeService: $verificationService,
        );

        $response = $service->resendVerification(Request::create('/api/access/verification/resend', 'POST'));
        $payload = $this->decodeResponse($response);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('verification_resend_rate_limited', $payload['code']);
    }

    public function testLogoutRevokesBearerMobileSessionWhenTokenIsPresent(): void
    {
        $tokens = $this->createMock(AccessMobileTokenServiceInterface::class);
        $tokens->expects(self::once())->method('revoke')->with('opaque-token');
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('signOut');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $service = new AccessApiFlowService(
            $authentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
            mobileTokenService: $tokens,
        );
        $request = Request::create('/api/access/logout', 'POST');
        $request->attributes->set(\App\Accessing\Authenticator\AccessBearerAuthenticator::REQUEST_ATTRIBUTE, 'opaque-token');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $payload = $this->decodeResponse($service->logout($request));
        self::assertSame('unauthenticated', $payload['status']);
    }

    public function testRequestRecoveryCoversValidationUnavailableDeliveryFailureAndSuccess(): void
    {
        $base = fn (?AccessRecoveryServiceInterface $recovery): AccessApiFlowService => new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            recoveryService: $recovery,
        );

        self::assertSame(422, $base($this->createMock(AccessRecoveryServiceInterface::class))
            ->requestRecovery(Request::create('/api/access/recovery', 'POST', content: '{}'))->getStatusCode());
        self::assertSame(503, $base(null)->requestRecovery(Request::create(
            '/api/access/recovery',
            'POST',
            content: json_encode(['email' => 'recover@example.test'], JSON_THROW_ON_ERROR),
        ))->getStatusCode());

        $failing = $this->createMock(AccessRecoveryServiceInterface::class);
        $failing->method('requestPasswordRecovery')->willThrowException(
            new \App\Accessing\Exception\AccessNotificationDeliveryException(),
        );
        $failurePayload = $this->decodeResponse($base($failing)->requestRecovery(Request::create(
            '/api/access/recovery',
            'POST',
            content: json_encode(['email' => 'recover@example.test'], JSON_THROW_ON_ERROR),
        )));
        self::assertSame('notification_delivery_unavailable', $failurePayload['code']);

        $success = $this->createMock(AccessRecoveryServiceInterface::class);
        $success->expects(self::once())->method('requestPasswordRecovery')->with(
            'recover@example.test',
            self::isInstanceOf(Request::class),
        )->willReturn(null);
        $successResponse = $base($success)->requestRecovery(Request::create(
            '/api/access/recovery',
            'POST',
            content: json_encode(['email' => 'recover@example.test'], JSON_THROW_ON_ERROR),
        ));
        self::assertSame(202, $successResponse->getStatusCode());
        self::assertSame('recovery_requested', $this->decodeResponse($successResponse)['status']);
    }

    public function testPasskeyAuthenticationOptionsCoversUnavailableAndSuccess(): void
    {
        $base = fn (?\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface $passkeys): AccessApiFlowService => new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            passkeyAuthenticationService: $passkeys,
        );
        $request = Request::create('https://example.test/api/access/passkey/authentication/options', 'POST');
        self::assertSame(503, $base(null)->passkeyAuthenticationOptions($request)->getStatusCode());

        $passkeys = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface::class);
        $passkeys->expects(self::once())->method('issueOptions')->with(
            self::callback(static fn (\App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO $config): bool => 'example.test' === $config->id),
        )->willReturn(new \App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO('challenge', 'example.test', []));
        $payload = $this->decodeResponse($base($passkeys)->passkeyAuthenticationOptions($request));
        $publicKey = $payload['publicKey'] ?? null;
        self::assertIsArray($publicKey);
        self::assertSame('challenge', $publicKey['challenge'] ?? null);
    }

    public function testPasskeyRegistrationOptionsCoversAuthServiceAndSuccessBranches(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $withoutUser = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
        );
        self::assertSame(401, $withoutUser->passkeyRegistrationOptions(Request::create('https://example.test/passkey'))->getStatusCode());

        $user = new AccessEntity('passkey-options@example.test', 'Passkey Options');
        $userSecurity = $this->createMock(Security::class);
        $userSecurity->method('getUser')->willReturn($user);
        $missingService = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $userSecurity,
        );
        self::assertSame(503, $missingService->passkeyRegistrationOptions(Request::create('https://example.test/passkey'))->getStatusCode());

        $passkeys = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface::class);
        $passkeys->expects(self::once())->method('issueOptions')->willReturn(
            new \App\Accessing\DTO\AccessPasskeyRegistrationOptionsDTO(
                'registration-challenge',
                ['id' => 'example.test', 'name' => 'Example'],
                ['id' => 'handle', 'name' => 'passkey-options@example.test', 'displayName' => 'Passkey Options'],
                [],
                [],
                300000,
            ),
        );
        $service = new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $userSecurity,
            passkeyRegistrationService: $passkeys,
        );
        $payload = $this->decodeResponse($service->passkeyRegistrationOptions(Request::create('https://example.test/passkey')));
        $publicKey = $payload['publicKey'] ?? null;
        self::assertIsArray($publicKey);
        self::assertSame('registration-challenge', $publicKey['challenge'] ?? null);
    }

    public function testPasskeyRegistrationCompleteCoversValidationDomainFailureAndSuccess(): void
    {
        $user = new AccessEntity('passkey-complete@example.test', 'Passkey Complete');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $base = fn (?\App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface $passkeys): AccessApiFlowService => new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
            passkeyRegistrationService: $passkeys,
        );
        self::assertSame(503, $base(null)->passkeyRegistrationComplete(Request::create('https://example.test/passkey', 'POST'))->getStatusCode());

        $passkeys = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface::class);
        self::assertSame(422, $base($passkeys)->passkeyRegistrationComplete(Request::create(
            'https://example.test/passkey',
            'POST',
            content: json_encode(['name' => 'Laptop'], JSON_THROW_ON_ERROR),
        ))->getStatusCode());

        $failing = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface::class);
        $failing->method('complete')->willThrowException(new \DomainException('registration rejected'));
        $failure = $base($failing)->passkeyRegistrationComplete(Request::create(
            'https://example.test/passkey',
            'POST',
            content: json_encode(['name' => 'Laptop', 'credential' => ['challenge' => 'abc']], JSON_THROW_ON_ERROR),
        ));
        self::assertSame(422, $failure->getStatusCode());

        $credential = new \App\Accessing\Entity\AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'public-key', ['internal'], 'Laptop');
        $success = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface::class);
        $success->method('complete')->willReturn($credential);
        $successResponse = $base($success)->passkeyRegistrationComplete(Request::create(
            'https://example.test/passkey',
            'POST',
            content: json_encode(['name' => 'Laptop', 'credential' => ['challenge' => 'abc']], JSON_THROW_ON_ERROR),
        ));
        self::assertSame(201, $successResponse->getStatusCode());
        $payload = $this->decodeResponse($successResponse);
        $credentialPayload = $payload['credential'] ?? null;
        self::assertIsArray($credentialPayload);
        self::assertSame('credential-id', $credentialPayload['id'] ?? null);
    }

    public function testPasskeyAuthenticationCompleteCoversUnavailableValidationFailureAndSuccess(): void
    {
        $tokens = $this->createMock(AccessMobileTokenServiceInterface::class);
        $user = new AccessEntity('passkey-auth-api@example.test', 'Passkey Auth');
        $tokens->method('issue')->willReturn(new AccessMobileTokenPairDTO(
            'access-token',
            'refresh-token',
            new \DateTimeImmutable('+15 minutes'),
            new \DateTimeImmutable('+30 days'),
            'session-id',
        ));
        $base = fn (?\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface $passkeys): AccessApiFlowService => new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            mobileTokenService: $tokens,
            passkeyAuthenticationService: $passkeys,
        );
        self::assertSame(503, $base(null)->passkeyAuthenticationComplete(Request::create('https://example.test/passkey', 'POST'))->getStatusCode());

        $passkeys = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface::class);
        self::assertSame(422, $base($passkeys)->passkeyAuthenticationComplete(Request::create(
            'https://example.test/passkey',
            'POST',
            content: '{}',
        ))->getStatusCode());

        $failing = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface::class);
        $failing->method('complete')->willThrowException(new \DomainException('authentication rejected'));
        self::assertSame(401, $base($failing)->passkeyAuthenticationComplete(Request::create(
            'https://example.test/passkey',
            'POST',
            content: json_encode(['credential' => ['credentialId' => 'id']], JSON_THROW_ON_ERROR),
        ))->getStatusCode());

        $success = $this->createMock(\App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface::class);
        $success->method('complete')->willReturn($user);
        $request = Request::create(
            'https://example.test/passkey',
            'POST',
            content: json_encode(['credential' => ['credentialId' => 'id']], JSON_THROW_ON_ERROR),
            server: ['HTTP_X_DEVICE_NAME' => 'Coverage Device'],
        );
        $successResponse = $base($success)->passkeyAuthenticationComplete($request);
        self::assertSame(200, $successResponse->getStatusCode());
        self::assertSame('authenticated', $this->decodeResponse($successResponse)['status']);
    }

    public function testConfirmVerificationCoversValidationUnavailableInvalidAndSuccess(): void
    {
        $user = new AccessEntity('confirm@example.test', 'Confirm');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $base = fn (?AccessVerificationChallengeServiceInterface $verification): AccessApiFlowService => new AccessApiFlowService(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $security,
            verificationChallengeService: $verification,
        );

        self::assertSame(422, $base($this->createMock(AccessVerificationChallengeServiceInterface::class))->confirmVerification(
            Request::create('/api/access/verification/confirm', 'POST', content: '{}'),
        )->getStatusCode());
        self::assertSame(503, $base(null)->confirmVerification(Request::create(
            '/api/access/verification/confirm', 'POST', content: json_encode(['code' => '123456'], JSON_THROW_ON_ERROR),
        ))->getStatusCode());

        $invalid = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $invalid->method('completeEmailVerification')->willReturn(false);
        self::assertSame(422, $base($invalid)->confirmVerification(Request::create(
            '/api/access/verification/confirm', 'POST', content: json_encode(['code' => 'bad'], JSON_THROW_ON_ERROR),
        ))->getStatusCode());

        $success = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $success->expects(self::once())->method('completeEmailVerification')->with($user, '123456')->willReturn(true);
        $payload = $this->decodeResponse($base($success)->confirmVerification(Request::create(
            '/api/access/verification/confirm', 'POST', content: json_encode(['code' => '123456'], JSON_THROW_ON_ERROR),
        )));
        self::assertSame('authenticated', $payload['status']);
    }

    public function testSecondFactorChallengeAndVerificationCoverMobileContinuation(): void
    {
        $user = new AccessEntity('second-factor@example.test', 'Second Factor');
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $pendingEntity = new \App\Accessing\Entity\AccessMobilePendingAuthEntity(
            $user, 'pending-token', AccessMobilePendingPurpose::SecondFactor, 'iPhone', $now, $now->modify('+10 minutes'),
        );
        $pending = $this->createMock(AccessMobilePendingAuthServiceInterface::class);
        $pending->method('resolve')->with('pending-token', AccessMobilePendingPurpose::SecondFactor)->willReturn($pendingEntity);
        $pending->method('consume')->willReturn($pendingEntity);

        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('completeMobileSecondFactor')->with($user, self::isInstanceOf(Request::class));
        $secondFactor = $this->createMock(\App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface::class);
        $secondFactor->expects(self::once())->method('verifyChallenge')->with($user, '654321')->willReturn(true);
        $tokens = $this->createMock(AccessMobileTokenServiceInterface::class);
        $tokens->expects(self::once())->method('issue')->with($user, 'iPhone')->willReturn(new AccessMobileTokenPairDTO(
            'mobile-access', 'mobile-refresh', $now->modify('+15 minutes'), $now->modify('+30 days'), 'session-id',
        ));

        $service = new AccessApiFlowService(
            $authentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            secondFactorService: $secondFactor,
            mobileTokenService: $tokens,
            mobilePendingAuthService: $pending,
        );

        $challenge = $service->challengeSecondFactor(Request::create(
            '/api/access/second-factor/challenge', 'POST', content: json_encode(['pendingToken' => 'pending-token'], JSON_THROW_ON_ERROR),
        ));
        self::assertSame(202, $challenge->getStatusCode());
        self::assertSame('second_factor_pending', $this->decodeResponse($challenge)['status']);

        $verified = $service->verifySecondFactor(Request::create(
            '/api/access/second-factor/verify', 'POST', content: json_encode(['pendingToken' => 'pending-token', 'code' => '654321'], JSON_THROW_ON_ERROR),
        ));
        self::assertSame(200, $verified->getStatusCode());
        self::assertSame('mobile-access', $this->decodeResponse($verified)['accessToken']);
    }

    public function testSecondFactorVerificationRejectsMissingSessionUnavailableServiceAndInvalidCode(): void
    {
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->method('getPendingSecondFactorUserId')->willReturn(null);
        $service = new AccessApiFlowService(
            $authentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
        );
        $request = Request::create('/api/access/second-factor/verify', 'POST', content: json_encode(['code' => '123456'], JSON_THROW_ON_ERROR));
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::assertSame(401, $service->verifySecondFactor($request)->getStatusCode());

        $user = new AccessEntity('pending@example.test');
        $repository = $this->createMock(\App\Accessing\RepositoryInterface\AccessRepositoryInterface::class);
        $repository->method('findById')->with(12)->willReturn($user);
        $pendingAuthentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $pendingAuthentication->method('getPendingSecondFactorUserId')->willReturn(12);
        $pendingRequest = Request::create('/api/access/second-factor/verify', 'POST', content: json_encode(['code' => '123456'], JSON_THROW_ON_ERROR));
        $pendingRequest->setSession(new Session(new MockArraySessionStorage()));

        $unavailable = new AccessApiFlowService(
            $pendingAuthentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            accessRepository: $repository,
        );
        self::assertSame(503, $unavailable->verifySecondFactor($pendingRequest)->getStatusCode());

        $secondFactor = $this->createMock(\App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface::class);
        $secondFactor->method('verifyChallenge')->willReturn(false);
        $invalid = new AccessApiFlowService(
            $pendingAuthentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new AccessApiJsonResponder(),
            $this->createMock(Security::class),
            accessRepository: $repository,
            secondFactorService: $secondFactor,
        );
        self::assertSame(422, $invalid->verifySecondFactor($pendingRequest)->getStatusCode());
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
