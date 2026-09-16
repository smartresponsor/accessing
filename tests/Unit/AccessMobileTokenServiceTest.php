<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\RepositoryInterface\AccessMobileSessionRepositoryInterface;
use App\Accessing\Service\Mobile\AccessMobileTokenService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class AccessMobileTokenServiceTest extends TestCase
{
    public function testIssueAuthenticateRotateAndRevokeLifecycle(): void
    {
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $storedSession = null;
        $currentAccessToken = null;

        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->method('save')->willReturnCallback(static function (AccessMobileSessionEntity $session) use (&$storedSession): void {
            $storedSession = $session;
        });

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::exactly(3))->method('record');

        $service = new AccessMobileTokenService($repository, $clock, $events, 900, 3600);
        $user = new AccessEntity('mobile@example.test', 'Mobile User');
        $tokens = $service->issue($user, 'iPhone');
        $currentAccessToken = $tokens->accessToken;

        self::assertInstanceOf(AccessMobileSessionEntity::class, $storedSession);
        self::assertSame($user, $storedSession->getUser());
        self::assertSame('iPhone', $storedSession->getDeviceName());

        $repository->method('findOneByAccessTokenHash')->willReturnCallback(
            static function (string $hash) use (&$currentAccessToken, &$storedSession): ?AccessMobileSessionEntity {
                return hash('sha256', $currentAccessToken) === $hash ? $storedSession : null;
            },
        );
        self::assertSame($user, $service->authenticate($tokens->accessToken));

        $repository->method('findOneByRefreshTokenHash')->willReturnCallback(
            static fn (string $hash): ?AccessMobileSessionEntity => hash('sha256', $tokens->refreshToken) === $hash ? $storedSession : null,
        );
        $repository->method('findOneByPreviousRefreshTokenHash')->willReturn(null);

        $rotated = $service->rotate($tokens->refreshToken);
        $currentAccessToken = $rotated->accessToken;
        self::assertNotSame($tokens->accessToken, $rotated->accessToken);
        self::assertNotSame($tokens->refreshToken, $rotated->refreshToken);
        self::assertSame($tokens->sessionId, $rotated->sessionId);
        self::assertTrue($storedSession->hasAccessToken($rotated->accessToken));
        self::assertTrue($storedSession->hasRefreshToken($rotated->refreshToken));

        $service->revoke($rotated->accessToken);
        self::assertFalse($storedSession->isAccessActive($now));
        self::assertFalse($storedSession->isRefreshActive($now));
    }

    public function testInvalidTokensAndRefreshReuseFailClosed(): void
    {
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $user = new AccessEntity('reuse@example.test');
        $session = new AccessMobileSessionEntity(
            $user,
            'session-id',
            'access-token',
            'refresh-token',
            'device',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );
        $session->rotate(
            'rotated-access-token',
            'rotated-refresh-token',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );

        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->method('findOneByAccessTokenHash')->willReturn(null);
        $repository->method('findOneByRefreshTokenHash')->willReturn(null);
        $repository->method('findOneByPreviousRefreshTokenHash')->willReturn($session);
        $repository->expects(self::once())->method('save')->with($session, true);

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record');
        $service = new AccessMobileTokenService($repository, $clock, $events, 900, 3600);

        try {
            $service->authenticate('invalid-access-token');
            self::fail('Invalid access tokens must fail closed.');
        } catch (\DomainException $exception) {
            self::assertSame('Mobile access token is invalid.', $exception->getMessage());
        }

        try {
            $service->rotate('refresh-token');
            self::fail('Reused refresh tokens must fail closed.');
        } catch (\DomainException $exception) {
            self::assertSame('Mobile refresh token is invalid.', $exception->getMessage());
        }

        self::assertFalse($session->isRefreshActive($now));
    }

    public function testInvalidTtlConfigurationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mobile token TTL configuration is invalid.');

        new AccessMobileTokenService(
            $this->createMock(AccessMobileSessionRepositoryInterface::class),
            $this->createMock(ClockInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            59,
            60,
        );
    }

    public function testAuthenticateRejectsMismatchedAndExpiredStoredSessions(): void
    {
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $user = new AccessEntity('authenticate-branches@example.test');
        $session = new AccessMobileSessionEntity(
            $user,
            'session-id',
            'stored-token',
            'refresh-token',
            'device',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );
        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->method('findOneByAccessTokenHash')->willReturn($session);
        $service = new AccessMobileTokenService(
            $repository,
            $clock,
            $this->createMock(AccessSecurityEventServiceInterface::class),
            900,
            3600,
        );

        try {
            $service->authenticate('different-token');
            self::fail('Mismatched stored access token must fail closed.');
        } catch (\DomainException $exception) {
            self::assertSame('Mobile access token is invalid.', $exception->getMessage());
        }

        $expired = new AccessMobileSessionEntity(
            $user,
            'expired-session',
            'expired-token',
            'expired-refresh',
            'device',
            $now->modify('-2 hours'),
            $now->modify('-1 hour'),
            $now->modify('+1 hour'),
        );
        $repository->method('findOneByAccessTokenHash')->willReturn($expired);

        $this->expectException(\DomainException::class);
        $service->authenticate('expired-token');
    }

    public function testRotateRejectsMismatchedOrExpiredCurrentRefreshToken(): void
    {
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $user = new AccessEntity('rotate-branches@example.test');
        $session = new AccessMobileSessionEntity(
            $user,
            'session-id',
            'access-token',
            'stored-refresh',
            'device',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );
        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->method('findOneByRefreshTokenHash')->willReturn($session);
        $service = new AccessMobileTokenService(
            $repository,
            $clock,
            $this->createMock(AccessSecurityEventServiceInterface::class),
            900,
            3600,
        );

        try {
            $service->rotate('different-refresh');
            self::fail('Mismatched current refresh token must fail closed.');
        } catch (\DomainException $exception) {
            self::assertSame('Mobile refresh token is invalid.', $exception->getMessage());
        }

        $expired = new AccessMobileSessionEntity(
            $user,
            'expired-session',
            'access-token-2',
            'expired-refresh',
            'device',
            $now->modify('-2 hours'),
            $now->modify('-1 hour'),
            $now->modify('-1 minute'),
        );
        $repository->method('findOneByRefreshTokenHash')->willReturn($expired);

        $this->expectException(\DomainException::class);
        $service->rotate('expired-refresh');
    }

    public function testRevokeIgnoresStoredSessionWhenPlainTokenDoesNotMatch(): void
    {
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $session = new AccessMobileSessionEntity(
            new AccessEntity('revoke-mismatch@example.test'),
            'session-id',
            'stored-token',
            'refresh-token',
            'device',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );
        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->method('findOneByAccessTokenHash')->willReturn($session);
        $repository->expects(self::never())->method('save');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::never())->method('record');

        (new AccessMobileTokenService($repository, $clock, $events, 900, 3600))->revoke('different-token');
        self::assertTrue($session->isAccessActive($now));
    }

    public function testRefreshTtlMustBeGreaterThanAccessTtl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccessMobileTokenService(
            $this->createMock(AccessMobileSessionRepositoryInterface::class),
            $this->createMock(ClockInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            900,
            900,
        );
    }

    public function testRevokeIgnoresUnknownToken(): void
    {
        $repository = $this->createMock(AccessMobileSessionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findOneByAccessTokenHash')
            ->with(hash('sha256', 'unknown'))
            ->willReturn(null);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::never())->method('record');

        $service = new AccessMobileTokenService(
            $repository,
            $this->createMock(ClockInterface::class),
            $events,
            900,
            3600,
        );
        $service->revoke('unknown');
    }
}
