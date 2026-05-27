<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\Tests\Support\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AccountAuthenticationServiceTest extends DatabaseTestCase
{
    public function testRegisterThenSignInWithSamePasswordWorks(): void
    {
        $this->refreshDatabase();

        /** @var AccessingAccountRegistrationServiceInterface $registrationService */
        $registrationService = static::getContainer()->get(AccessingAccountRegistrationServiceInterface::class);
        /** @var AccessingAccountAuthenticationServiceInterface $authenticationService */
        $authenticationService = static::getContainer()->get(AccessingAccountAuthenticationServiceInterface::class);

        $request = new AccountRegistrationRequest();
        $request->email = 'taa0662621456000@gmail.com';
        $request->plainPassword = 'taa0662621456000@gmail.com';
        $request->displayName = 'Auth Check';

        $registrationService->register($request);

        $signInRequest = Request::create('/sign-in', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $signInRequest->setSession(new Session(new MockArraySessionStorage()));

        $signInResult = $authenticationService->attemptPasswordSignIn(
            'taa0662621456000@gmail.com',
            'taa0662621456000@gmail.com',
            $signInRequest,
        );

        self::assertTrue($signInResult->authenticated);
        self::assertNotNull($signInResult->account);
        self::assertSame('taa0662621456000@gmail.com', $signInResult->account?->getEmail());
    }
}
