<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Repository\AccessRepository;
use App\Accessing\Repository\AccessSecurityEventRepository;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AccessAuthenticationServiceTest extends AccessDatabaseTestCase
{
    public function testRegisterThenSignInWithSamePasswordWorks(): void
    {
        $this->refreshDatabase();

        /** @var AccessRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessRegistrationServiceInterface::class);
        /** @var AccessAuthenticationServiceInterface $authenticationService */
        $authenticationService = static::getContainer()->get(AccessAuthenticationServiceInterface::class);

        $email = sprintf('auth-%s@example.test', bin2hex(random_bytes(6)));
        $password = sprintf('Auth-pass-%s', bin2hex(random_bytes(6)));

        $request = new AccessRegistrationRequest();
        $request->email = $email;
        $request->plainPassword = $password;
        $request->displayName = 'Auth Check';

        $registrationService->register($request);

        $signInRequest = Request::create('/access/signin', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $signInRequest->setSession(new Session(new MockArraySessionStorage()));

        $signInResult = $authenticationService->attemptPasswordSignIn(
            $email,
            $password,
            $signInRequest,
        );

        self::assertTrue($signInResult->authenticated);
        self::assertNotNull($signInResult->user);
        self::assertSame($email, $signInResult->user->getEmail());
    }

    public function testLockedAccountSignInAttemptRecordsDedicatedRedactedEvent(): void
    {
        $this->refreshDatabase();

        /** @var AccessRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessRegistrationServiceInterface::class);
        /** @var AccessAuthenticationServiceInterface $authenticationService */
        $authenticationService = static::getContainer()->get(AccessAuthenticationServiceInterface::class);
        /** @var AccessRepository $userRepository */
        $userRepository = static::getContainer()->get(AccessRepository::class);
        /** @var AccessSecurityEventRepository $securityEventRepository */
        $securityEventRepository = static::getContainer()->get(AccessSecurityEventRepository::class);

        $email = sprintf('locked-%s@example.test', bin2hex(random_bytes(6)));
        $password = sprintf('Locked-pass-%s', bin2hex(random_bytes(6)));

        $registration = new AccessRegistrationRequest();
        $registration->email = $email;
        $registration->plainPassword = $password;
        $registration->displayName = 'Locked Account';
        $registrationService->register($registration);

        $user = $userRepository->findOneByEmailAddress($email);
        self::assertNotNull($user);
        $user->lockUntil(new \DateTimeImmutable('+15 minutes'));
        $userRepository->save($user, true);

        $request = Request::create('/access/signin', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('User-Agent', 'AccessingLockedAccountTest/1.0');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $result = $authenticationService->attemptPasswordSignIn($email, $password, $request);

        self::assertFalse($result->authenticated);

        $events = $securityEventRepository->findRecentEventsForUser($user, 10);
        $lockedAttempts = array_values(array_filter(
            $events,
            static fn ($event): bool => AccessSecurityEventType::LockedAccountSignInAttempt === $event->getEventType(),
        ));

        self::assertCount(1, $lockedAttempts);
        self::assertSame(AccessSecurityEventSeverity::Warning, $lockedAttempts[0]->getSeverity());
        self::assertSame('account_locked', $lockedAttempts[0]->getContext()['reason'] ?? null);
        self::assertArrayHasKey('lockExpiresAt', $lockedAttempts[0]->getContext());
        self::assertArrayNotHasKey('emailAddress', $lockedAttempts[0]->getContext());
        self::assertArrayNotHasKey('password', $lockedAttempts[0]->getContext());
        self::assertStringNotContainsString($password, json_encode($lockedAttempts[0]->getContext(), JSON_THROW_ON_ERROR));
    }
}
