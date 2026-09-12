<?php

declare(strict_types=1);

namespace App\Accessing\Service\Mobile;

use App\Accessing\DTO\AccessMobilePendingTokenDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\RepositoryInterface\AccessMobilePendingAuthRepositoryInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobilePendingAuthServiceInterface;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use Psr\Clock\ClockInterface;

/**
 * Defines the mobile pending auth service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessMobilePendingAuthService implements AccessMobilePendingAuthServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessMobilePendingAuthRepositoryInterface $repository,
        private ClockInterface $clock,
        private int $accessingMobilePendingAuthTtlSeconds = 600,
    ) {
        if ($accessingMobilePendingAuthTtlSeconds < 60) {
            throw new \InvalidArgumentException('Mobile pending-auth TTL must be at least 60 seconds.');
        }
    }

    /**
     * Executes the issue operation within the canonical Accessing component workflow.
     */
    public function issue(AccessEntity $user, AccessMobilePendingPurpose $purpose, string $deviceName): AccessMobilePendingTokenDTO
    {
        $now = $this->clock->now();
        $plainToken = self::token();
        $expiresAt = $now->modify(sprintf('+%d seconds', $this->accessingMobilePendingAuthTtlSeconds));
        $pendingAuth = new AccessMobilePendingAuthEntity($user, $plainToken, $purpose, $deviceName, $now, $expiresAt);
        $this->repository->save($pendingAuth, true);

        return new AccessMobilePendingTokenDTO($plainToken, $expiresAt);
    }

    /**
     * Executes the resolve operation within the canonical Accessing component workflow.
     */
    public function resolve(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity
    {
        $pendingAuth = $this->repository->findOneByTokenHash(hash('sha256', trim($plainToken)));
        $now = $this->clock->now();
        if (!$pendingAuth instanceof AccessMobilePendingAuthEntity || !$pendingAuth->hasToken($plainToken) || !$pendingAuth->isUsable($purpose, $now)) {
            throw new \DomainException('Pending mobile authentication token is invalid or expired.');
        }

        return $pendingAuth;
    }

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(string $plainToken, AccessMobilePendingPurpose $purpose): AccessMobilePendingAuthEntity
    {
        $pendingAuth = $this->resolve($plainToken, $purpose);
        $pendingAuth->consume($this->clock->now());
        $this->repository->save($pendingAuth, true);

        return $pendingAuth;
    }

    /**
     * Executes the token operation within the canonical Accessing component workflow.
     */
    private static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
