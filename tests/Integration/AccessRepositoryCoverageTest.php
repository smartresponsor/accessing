<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\Repository\AccessRepository;
use App\Accessing\Repository\AccessSecurityEventRepository;
use App\Accessing\Repository\AccessSessionRepository;
use App\Accessing\Repository\AccessVerificationChallengeRepository;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\ValueObject\AccessVerificationChallengeType;

final class AccessRepositoryCoverageTest extends AccessDatabaseTestCase
{
    public function testAccessRepositoryPersistsFindsListsAndRemovesUsers(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $repository */
        $repository = static::getContainer()->get(AccessRepository::class);

        $first = new AccessEntity('Repo-One@Example.Test', 'First');
        $second = new AccessEntity('repo-two@example.test', 'Second');
        $repository->save($first);
        $repository->save($second, true);

        self::assertSame($first, $repository->findById((int) $first->getId()));
        self::assertNull($repository->findById(999999));
        self::assertSame($first, $repository->findOneByEmail('repo-one@example.test'));
        self::assertSame($second, $repository->findOneByEmailAddress(' REPO-TWO@EXAMPLE.TEST '));
        self::assertNull($repository->findOneByEmailAddress('missing@example.test'));
        self::assertCount(1, $repository->findRecentUsers(1));

        $repository->remove($second);
        $repository->remove($second, true);
        self::assertNull($repository->findOneByEmailAddress('repo-two@example.test'));
    }

    public function testSessionRepositoryCoversActiveInvalidationAndCleanupLifecycle(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessSessionRepository $sessions */
        $sessions = static::getContainer()->get(AccessSessionRepository::class);

        $user = new AccessEntity('sessions@example.test');
        $users->save($user, true);
        $keep = new AccessSessionEntity($user, 'keep-session');
        $other = new AccessSessionEntity($user, 'other-session');
        $old = new AccessSessionEntity($user, 'old-session');
        $old->revoke(new \DateTimeImmutable('-2 days'));
        $sessions->save($keep);
        $sessions->save($other);
        $sessions->save($old, true);

        self::assertSame($keep, $sessions->findOneBySessionIdentifier('keep-session'));
        self::assertNull($sessions->findOneBySessionIdentifier('missing-session'));
        self::assertCount(2, $sessions->findActiveForUser($user));
        self::assertSame(1, $sessions->invalidateOtherActiveSessions($user, 'keep-session'));
        self::assertCount(1, $sessions->findActiveForUser($user));
        self::assertSame(2, $sessions->cleanupInvalidatedBefore(new \DateTimeImmutable('+1 day')));
    }

    public function testSecurityEventRepositoryPersistsAndListsEvents(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessSecurityEventRepository $events */
        $events = static::getContainer()->get(AccessSecurityEventRepository::class);

        $user = new AccessEntity('events@example.test');
        $users->save($user, true);
        $event = new AccessSecurityEventEntity(
            AccessSecurityEventType::SignInFailed,
            AccessSecurityEventSeverity::Warning,
            $user,
            context: ['reason' => 'coverage'],
        );
        $events->save($event, true);

        self::assertSame([$event], $events->findRecentEvents(1));
        self::assertSame([$event], $events->findRecentEventsForUser($user, 1));

        $transientUser = new AccessEntity('transient-event@example.test');
        $transient = new AccessSecurityEventEntity(AccessSecurityEventType::SignInFailed, AccessSecurityEventSeverity::Info, $transientUser);
        $events->save($transient, true);
        self::assertNotNull($transientUser->getId());
    }

    public function testVerificationChallengeRepositoryQueriesKindsAndCleansExpiredRows(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessVerificationChallengeRepository $challenges */
        $challenges = static::getContainer()->get(AccessVerificationChallengeRepository::class);

        $user = new AccessEntity('verification-repository@example.test');
        $users->save($user, true);
        $future = new \DateTimeImmutable('+1 hour');

        foreach ([
            AccessVerificationChallengeType::EmailVerification,
            AccessVerificationChallengeType::PhoneVerification,
            AccessVerificationChallengeType::PasswordRecovery,
        ] as $index => $type) {
            $challenge = new AccessVerificationChallengeEntity($user, $type, 'target-'.$index, 'token-'.$index, $future);
            $challenges->save($challenge);
            $challenges->save($challenge, true);
            self::assertSame($challenge, $challenges->findLatestActiveForUser($user, $type));
        }

        $expired = new AccessVerificationChallengeEntity(
            $user,
            AccessVerificationChallengeType::EmailVerification,
            'expired@example.test',
            'expired-token',
            new \DateTimeImmutable('-1 hour'),
        );
        $challenges->save($expired, true);
        self::assertContains($expired, $challenges->findExpiredActiveChallenges(new \DateTimeImmutable()));

        $completed = new AccessVerificationChallengeEntity(
            $user,
            AccessVerificationChallengeType::PhoneVerification,
            '+15555550123',
            'completed-token',
            $future,
        );
        $completed->markCompleted(new \DateTimeImmutable('-2 days'));
        $challenges->save($completed, true);

        self::assertSame(2, $challenges->cleanupExpiredConsumedBefore(new \DateTimeImmutable()));
        self::assertSame([], $challenges->findExpiredActiveChallenges(new \DateTimeImmutable()));
    }
}
