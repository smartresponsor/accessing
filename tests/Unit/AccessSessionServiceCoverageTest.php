<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\RepositoryInterface\AccessSessionRepositoryInterface;
use App\Accessing\Service\Session\AccessSessionService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class AccessSessionServiceCoverageTest extends TestCase
{
    public function testRegisterSessionCreatesAndFlushesNewSession(): void
    {
        $user = new AccessEntity('session-service@example.test');
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('session-123');
        $request = Request::create('/access');
        $request->server->set('REMOTE_ADDR', '203.0.113.20');
        $request->headers->set('User-Agent', 'Coverage Agent');
        $request->setSession($session);

        $repository = $this->createMock(AccessSessionRepositoryInterface::class);
        $repository->expects(self::once())->method('findOneBySessionIdentifier')->with('session-123')->willReturn(null);
        $saved = [];
        $repository->expects(self::exactly(3))->method('save')->willReturnCallback(
            static function (AccessSessionEntity $entity, bool $flush = false) use (&$saved): void {
                $saved[] = [$entity, $flush];
            },
        );

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SessionRegistered,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['sessionIdentifier' => 'session-123'],
            false,
        );

        $service = new AccessSessionService($repository, $events, 30);
        $service->registerSession($user, $request);

        self::assertCount(3, $saved);
        self::assertFalse($saved[0][1]);
        self::assertFalse($saved[1][1]);
        self::assertTrue($saved[2][1]);
        self::assertSame('session-123', $saved[0][0]->getSessionIdentifier());
        self::assertSame('203.0.113.20', $saved[0][0]->getIpAddress());
        self::assertSame('Coverage Agent', $saved[0][0]->getUserAgent());
        self::assertTrue($user->getUserSessions()->contains($saved[0][0]));
    }

    public function testRegisterSessionReusesExistingSessionWithoutFinalFlushWhenDisabled(): void
    {
        $user = new AccessEntity('existing-session@example.test');
        $existing = new AccessSessionEntity($user, 'existing-id');
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('existing-id');
        $request = Request::create('/access');
        $request->setSession($session);

        $repository = $this->createMock(AccessSessionRepositoryInterface::class);
        $repository->method('findOneBySessionIdentifier')->willReturn($existing);
        $repository->expects(self::once())->method('save')->with($existing, false);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record');

        (new AccessSessionService($repository, $events, 30))->registerSession($user, $request, false);
    }

    public function testInvalidateCurrentSessionHonorsOwnership(): void
    {
        $owner = new AccessEntity('owner@example.test');
        $other = new AccessEntity('other@example.test');
        $entity = new AccessSessionEntity($owner, 'owned-session');
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('owned-session');

        $repository = $this->createMock(AccessSessionRepositoryInterface::class);
        $repository->method('findOneBySessionIdentifier')->willReturn($entity);
        $repository->expects(self::once())->method('save')->with($entity, true);
        (new AccessSessionService($repository, $this->createMock(AccessSecurityEventServiceInterface::class), 30))
            ->invalidateCurrentSession($owner, $session);
        self::assertNotNull($entity->getInvalidatedAt());

        $repositoryWithoutSave = $this->createMock(AccessSessionRepositoryInterface::class);
        $repositoryWithoutSave->method('findOneBySessionIdentifier')->willReturn($entity);
        $repositoryWithoutSave->expects(self::never())->method('save');
        (new AccessSessionService($repositoryWithoutSave, $this->createMock(AccessSecurityEventServiceInterface::class), 30))
            ->invalidateCurrentSession($other, $session);
    }

    public function testInvalidateOtherAndCleanupDelegateToRepository(): void
    {
        $user = new AccessEntity('delegate@example.test');
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('keep-id');
        $repository = $this->createMock(AccessSessionRepositoryInterface::class);
        $repository->expects(self::once())->method('invalidateOtherActiveSessions')->with($user, 'keep-id')->willReturn(4);
        $repository->expects(self::once())->method('cleanupInvalidatedBefore')->with(
            self::callback(static function (\DateTimeImmutable $before): bool {
                $days = (new \DateTimeImmutable())->diff($before)->days;

                return $days >= 29 && $days <= 31;
            }),
        )->willReturn(7);

        $service = new AccessSessionService($repository, $this->createMock(AccessSecurityEventServiceInterface::class), 30);
        self::assertSame(4, $service->invalidateOtherSessions($user, $session));
        self::assertSame(7, $service->cleanupSessions());
    }
}
