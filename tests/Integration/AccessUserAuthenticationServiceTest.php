<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccessUserRegistrationRequest;
use App\Accessing\ServiceInterface\User\AccessUserAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\User\AccessUserRegistrationServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AccessUserAuthenticationServiceTest extends AccessDatabaseTestCase
{
    public function testRegisterThenSignInWithSamePasswordWorks(): void
    {
        $this->refreshDatabase();

        /** @var AccessUserRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessUserRegistrationServiceInterface::class);
        /** @var AccessUserAuthenticationServiceInterface $authenticationService */
        $authenticationService = static::getContainer()->get(AccessUserAuthenticationServiceInterface::class);

        $request = new AccessUserRegistrationRequest();
        $request->email = 'taa0662621456000@gmail.com';
        $request->plainPassword = 'taa0662621456000@gmail.com';
        $request->displayName = 'Auth Check';

        $registrationService->register($request);

        $signInRequest = Request::create('/access/signin', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $signInRequest->setSession(new Session(new MockArraySessionStorage()));

        $signInResult = $authenticationService->attemptPasswordSignIn(
            'taa0662621456000@gmail.com',
            'taa0662621456000@gmail.com',
            $signInRequest,
        );

        self::assertTrue($signInResult->authenticated);
        self::assertNotNull($signInResult->user);
        self::assertSame('taa0662621456000@gmail.com', $signInResult->user?->getEmail());
    }
}
