<?php

declare(strict_types=1);

namespace App\Accessing\Service\Mobile;

use App\Accessing\Dto\AccessMobileTokenPair;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\RepositoryInterface\AccessMobileSessionRepositoryInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Psr\Clock\ClockInterface;

final readonly class AccessMobileTokenService implements AccessMobileTokenServiceInterface
{
    public function __construct(
        private AccessMobileSessionRepositoryInterface $repository,
        private ClockInterface $clock,
        private AccessSecurityEventServiceInterface $securityEventService,
        private int $accessingMobileAccessTtlSeconds = 900,
        private int $accessingMobileRefreshTtlSeconds = 2592000,
    ) {
        if ($accessingMobileAccessTtlSeconds < 60 || $accessingMobileRefreshTtlSeconds <= $accessingMobileAccessTtlSeconds) {
            throw new \InvalidArgumentException('Mobile token TTL configuration is invalid.');
        }
    }

    public function issue(AccessEntity $user, string $deviceName): AccessMobileTokenPair
    {
        $now = $this->clock->now();
        $accessToken = self::token();
        $refreshToken = self::token();
        $accessExpiresAt = $now->modify(sprintf('+%d seconds', $this->accessingMobileAccessTtlSeconds));
        $refreshExpiresAt = $now->modify(sprintf('+%d seconds', $this->accessingMobileRefreshTtlSeconds));
        $session = new AccessMobileSessionEntity($user, bin2hex(random_bytes(16)), $accessToken, $refreshToken, $deviceName, $now, $accessExpiresAt, $refreshExpiresAt);
        $this->repository->save($session, true);
        $this->securityEventService->record(
            AccessSecurityEventType::MobileSessionIssued,
            AccessSecurityEventSeverity::Info,
            $user,
            null,
            ['sessionId' => $session->getSessionId(), 'deviceName' => $deviceName],
        );

        return new AccessMobileTokenPair($accessToken, $refreshToken, $accessExpiresAt, $refreshExpiresAt, $session->getSessionId());
    }

    public function authenticate(string $accessToken): AccessEntity
    {
        $session = $this->repository->findOneByAccessTokenHash(hash('sha256', trim($accessToken)));
        if (!$session instanceof AccessMobileSessionEntity || !$session->hasAccessToken($accessToken) || !$session->isAccessActive($this->clock->now())) {
            throw new \DomainException('Mobile access token is invalid.');
        }

        return $session->getUser();
    }

    public function rotate(string $refreshToken): AccessMobileTokenPair
    {
        $refreshTokenHash = hash('sha256', trim($refreshToken));
        $session = $this->repository->findOneByRefreshTokenHash($refreshTokenHash);
        $now = $this->clock->now();

        if (!$session instanceof AccessMobileSessionEntity) {
            $reusedSession = $this->repository->findOneByPreviousRefreshTokenHash($refreshTokenHash);
            if ($reusedSession instanceof AccessMobileSessionEntity && $reusedSession->hasPreviousRefreshToken($refreshToken)) {
                $reusedSession->markRefreshReuseDetected($now);
                $this->repository->save($reusedSession, true);
                $this->securityEventService->record(
                    AccessSecurityEventType::MobileRefreshReuseDetected,
                    AccessSecurityEventSeverity::Warning,
                    $reusedSession->getUser(),
                    null,
                    ['sessionId' => $reusedSession->getSessionId()],
                );
            }

            throw new \DomainException('Mobile refresh token is invalid.');
        }

        if (!$session->hasRefreshToken($refreshToken) || !$session->isRefreshActive($now)) {
            throw new \DomainException('Mobile refresh token is invalid.');
        }

        $accessToken = self::token();
        $newRefreshToken = self::token();
        $accessExpiresAt = $now->modify(sprintf('+%d seconds', $this->accessingMobileAccessTtlSeconds));
        $refreshExpiresAt = $now->modify(sprintf('+%d seconds', $this->accessingMobileRefreshTtlSeconds));
        $session->rotate($accessToken, $newRefreshToken, $now, $accessExpiresAt, $refreshExpiresAt);
        $this->repository->save($session, true);
        $this->securityEventService->record(
            AccessSecurityEventType::MobileSessionRefreshed,
            AccessSecurityEventSeverity::Info,
            $session->getUser(),
            null,
            ['sessionId' => $session->getSessionId()],
        );

        return new AccessMobileTokenPair($accessToken, $newRefreshToken, $accessExpiresAt, $refreshExpiresAt, $session->getSessionId());
    }

    public function revoke(string $accessToken): void
    {
        $session = $this->repository->findOneByAccessTokenHash(hash('sha256', trim($accessToken)));
        if ($session instanceof AccessMobileSessionEntity && $session->hasAccessToken($accessToken)) {
            $session->revoke($this->clock->now());
            $this->repository->save($session, true);
            $this->securityEventService->record(
                AccessSecurityEventType::MobileSessionRevoked,
                AccessSecurityEventSeverity::Info,
                $session->getUser(),
                null,
                ['sessionId' => $session->getSessionId()],
            );
        }
    }

    private static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
