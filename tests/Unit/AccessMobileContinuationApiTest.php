<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessMobilePendingToken;
use App\Accessing\Dto\AccessMobileTokenPair;
use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use App\Accessing\Service\Http\Api\Access\ApiAccessFlowService;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobilePendingAuthServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AccessMobileContinuationApiTest extends TestCase
{
    public function testSecondFactorSignInIssuesPendingToken(): void
    {
        $user = new AccessEntity('second-factor@example.test', 'Second Factor');
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->method('attemptPasswordSignIn')->willReturn(AccessSignInResultDto::pendingSecondFactor($user));
        $pending = $this->createMock(AccessMobilePendingAuthServiceInterface::class);
        $pending->expects(self::once())
            ->method('issue')
            ->with($user, AccessMobilePendingPurpose::SecondFactor, 'Test iPhone')
            ->willReturn(new AccessMobilePendingToken('pending-2fa', new \DateTimeImmutable('2026-07-12T00:10:00+00:00')));

        $service = $this->service($authentication, pending: $pending);
        $request = Request::create(
            '/api/access/signin',
            'POST',
            content: json_encode(['email' => 'second-factor@example.test', 'password' => 'secret'], JSON_THROW_ON_ERROR),
            server: ['HTTP_X_DEVICE_NAME' => 'Test iPhone'],
        );
        $payload = $this->decode($service->signIn($request));

        self::assertSame('second_factor_pending', $payload['status']);
        self::assertSame('pending-2fa', $payload['pendingToken']);
        self::assertTrue($payload['requiresSecondFactor']);
    }

    public function testVerificationCompletionConsumesPendingAndIssuesMobileSession(): void
    {
        $user = new AccessEntity('verify-mobile@example.test', 'Verify Mobile');
        $pendingEntity = $this->pendingEntity($user, 'pending-verify', AccessMobilePendingPurpose::EmailVerification, 'Android');
        $pending = $this->createMock(AccessMobilePendingAuthServiceInterface::class);
        $pending->expects(self::once())->method('resolve')->with('pending-verify', AccessMobilePendingPurpose::EmailVerification)->willReturn($pendingEntity);
        $pending->expects(self::once())->method('consume')->with('pending-verify', AccessMobilePendingPurpose::EmailVerification)->willReturn($pendingEntity);
        $verification = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $verification->expects(self::once())->method('completeEmailVerification')->with($user, '123456')->willReturn(true);
        $tokens = $this->createMock(AccessMobileTokenServiceInterface::class);
        $tokens->expects(self::once())->method('issue')->with($user, 'Android')->willReturn($this->tokenPair());

        $service = $this->service(
            $this->createMock(AccessAuthenticationServiceInterface::class),
            pending: $pending,
            tokens: $tokens,
            verification: $verification,
        );
        $request = Request::create(
            '/api/access/verification/confirm',
            'POST',
            content: json_encode(['code' => '123456', 'pendingToken' => 'pending-verify'], JSON_THROW_ON_ERROR),
        );
        $payload = $this->decode($service->confirmVerification($request));

        self::assertSame('authenticated', $payload['status']);
        self::assertSame('access-token', $payload['accessToken']);
        self::assertSame('refresh-token', $payload['refreshToken']);
    }

    public function testSecondFactorCompletionUsesMobilePathAndConsumesPending(): void
    {
        $user = new AccessEntity('complete-2fa@example.test', 'Complete 2FA');
        $pendingEntity = $this->pendingEntity($user, 'pending-2fa', AccessMobilePendingPurpose::SecondFactor, 'iPhone');
        $pending = $this->createMock(AccessMobilePendingAuthServiceInterface::class);
        $pending->expects(self::once())->method('resolve')->willReturn($pendingEntity);
        $pending->expects(self::once())->method('consume')->willReturn($pendingEntity);
        $secondFactor = $this->createMock(AccessSecondFactorServiceInterface::class);
        $secondFactor->expects(self::once())->method('verifyChallenge')->with($user, '654321')->willReturn(true);
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('completeMobileSecondFactor')->with($user, self::isInstanceOf(Request::class));
        $authentication->expects(self::never())->method('completePendingSecondFactor');
        $tokens = $this->createMock(AccessMobileTokenServiceInterface::class);
        $tokens->method('issue')->willReturn($this->tokenPair());

        $service = $this->service($authentication, pending: $pending, tokens: $tokens, secondFactor: $secondFactor);
        $request = Request::create(
            '/api/access/second-factor/verify',
            'POST',
            content: json_encode(['code' => '654321', 'pendingToken' => 'pending-2fa'], JSON_THROW_ON_ERROR),
        );
        $payload = $this->decode($service->verifySecondFactor($request));

        self::assertSame('authenticated', $payload['status']);
        self::assertSame('access-token', $payload['accessToken']);
    }

    private function service(
        AccessAuthenticationServiceInterface $authentication,
        ?AccessMobilePendingAuthServiceInterface $pending = null,
        ?AccessMobileTokenServiceInterface $tokens = null,
        ?AccessVerificationChallengeServiceInterface $verification = null,
        ?AccessSecondFactorServiceInterface $secondFactor = null,
    ): ApiAccessFlowService {
        return new ApiAccessFlowService(
            $authentication,
            $this->createMock(AccessRegistrationServiceInterface::class),
            $this->createMock(AccessCurrentContextProviderInterface::class),
            new ApiAccessJsonResponder(),
            $this->createMock(Security::class),
            verificationChallengeService: $verification,
            secondFactorService: $secondFactor,
            mobileTokenService: $tokens,
            mobilePendingAuthService: $pending,
        );
    }

    private function pendingEntity(AccessEntity $user, string $token, AccessMobilePendingPurpose $purpose, string $deviceName): AccessMobilePendingAuthEntity
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');

        return new AccessMobilePendingAuthEntity($user, $token, $purpose, $deviceName, $now, $now->modify('+10 minutes'));
    }

    private function tokenPair(): AccessMobileTokenPair
    {
        return new AccessMobileTokenPair(
            'access-token',
            'refresh-token',
            new \DateTimeImmutable('2026-07-12T00:15:00+00:00'),
            new \DateTimeImmutable('2026-08-11T00:00:00+00:00'),
            'session-id',
        );
    }

    /** @return array<string, mixed> */
    private function decode(JsonResponse $response): array
    {
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        $result = [];

        foreach ($payload as $key => $value) {
            self::assertIsString($key);
            $result[$key] = $value;
        }

        return $result;
    }
}
