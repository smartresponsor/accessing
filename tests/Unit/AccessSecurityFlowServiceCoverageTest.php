<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO;
use App\Accessing\DTO\AccessRecoveryRequestDTO;
use App\Accessing\DTO\AccessRecoveryResetDTO;
use App\Accessing\DTO\AccessRegistrationRequestDTO;
use App\Accessing\DTO\AccessSignInRequestDTO;
use App\Accessing\DTO\AccessSignInResultDTO;
use App\Accessing\DTO\AccessVerificationCodeDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Factory\Rendering\AccessPageViewFactory;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\Service\Http\Access\AccessSecurityFlowService;
use App\Accessing\ServiceInterface\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccessSecurityFlowServiceCoverageTest extends TestCase
{
    public function testCanonicalRedirectEntrypointsAndAuthenticatedShortCircuits(): void
    {
        $guest = $this->service();
        $registerRedirect = $guest->register(Request::create('/access/register', 'GET'));
        self::assertInstanceOf(Response::class, $registerRedirect);
        self::assertSame(308, $registerRedirect->getStatusCode());
        self::assertSame('/access.register', $registerRedirect->headers->get('location'));
        $signInRedirect = $guest->signIn(Request::create('/access/signin', 'GET'));
        self::assertInstanceOf(Response::class, $signInRedirect);
        self::assertSame(308, $signInRedirect->getStatusCode());
        self::assertSame(308, $guest->signInTrailingSlash()->getStatusCode());

        $user = new AccessEntity('signed-in@example.test');
        $authenticated = $this->service($user);
        foreach ([
            $authenticated->register(Request::create('/access/register', 'POST')),
            $authenticated->signIn(Request::create('/access/signin', 'POST')),
            $authenticated->signInSubmit(Request::create('/access/signin', 'POST')),
        ] as $response) {
            self::assertInstanceOf(Response::class, $response);
            self::assertSame('/product/index', $response->headers->get('location'));
        }
    }

    public function testPasskeyEndpointsCoverAuthenticatedAndGuestFailureAndOptions(): void
    {
        $user = new AccessEntity('passkey-flow@example.test');
        $authenticated = $this->service($user);
        self::assertSame(409, $authenticated->passkeyAuthenticationOptions(Request::create('https://example.test/access/passkey/options'))->getStatusCode());
        self::assertSame(200, $authenticated->passkeyAuthenticationComplete(Request::create('https://example.test/access/passkey/complete', 'POST'))->getStatusCode());

        $passkeys = $this->createMock(AccessPasskeyAuthenticationServiceInterface::class);
        $passkeys->expects(self::once())->method('issueOptions')->willReturn(
            new AccessPasskeyAuthenticationOptionsDTO('challenge', 'example.test', []),
        );
        $guest = $this->service(passkeys: $passkeys);
        $options = $guest->passkeyAuthenticationOptions(Request::create('https://example.test/access/passkey/options'));
        self::assertSame(200, $options->getStatusCode());
        self::assertStringContainsString('challenge', (string) $options->getContent());

        $invalid = $guest->passkeyAuthenticationComplete(Request::create(
            'https://example.test/access/passkey/complete',
            'POST',
            content: '{}',
        ));
        self::assertSame(401, $invalid->getStatusCode());
    }

    public function testSecondFactorChallengeCoversMissingPendingUserAndRenderPath(): void
    {
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->method('getPendingSecondFactorUserId')->willReturn(null);
        $request = $this->requestWithSession('/access/second-factor');
        $missingPending = $this->service(authentication: $authentication)->secondFactorChallenge($request);
        self::assertInstanceOf(Response::class, $missingPending);
        self::assertSame('/access.signin', $missingPending->headers->get('location'));

        $missingAuthentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $missingAuthentication->method('getPendingSecondFactorUserId')->willReturn(42);
        $missingAuthentication->expects(self::once())->method('clearPendingSecondFactor');
        $repository = $this->createMock(AccessRepositoryInterface::class);
        $repository->method('findById')->with(42)->willReturn(null);
        $missingUser = $this->service(authentication: $missingAuthentication, users: $repository)
            ->secondFactorChallenge($this->requestWithSession('/access/second-factor'));
        self::assertInstanceOf(Response::class, $missingUser);
        self::assertSame('/access.signin', $missingUser->headers->get('location'));

        $user = new AccessEntity('pending-flow@example.test');
        $pendingAuthentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $pendingAuthentication->method('getPendingSecondFactorUserId')->willReturn(77);
        $pendingRepository = $this->createMock(AccessRepositoryInterface::class);
        $pendingRepository->method('findById')->with(77)->willReturn($user);
        $views = [];
        $response = $this->service(authentication: $pendingAuthentication, users: $pendingRepository, views: $views)
            ->secondFactorChallenge($this->requestWithSession('/access/second-factor'));
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('access.second_factor_challenge', $views[0]->view);
    }

    public function testSignOutSwitchUserAndRecoveryPagesCoverGuestAndAuthenticatedPaths(): void
    {
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->expects(self::exactly(2))->method('signOut')->with(null, self::isInstanceOf(Request::class));
        $guest = $this->service(authentication: $authentication);
        $signOut = $guest->signOut($this->requestWithSession('/access/signout'));
        $switchUser = $guest->switchUser($this->requestWithSession('/access/switch'));
        self::assertInstanceOf(Response::class, $signOut);
        self::assertInstanceOf(Response::class, $switchUser);
        self::assertSame('/access.signin', $signOut->headers->get('location'));
        self::assertSame('/access.signin', $switchUser->headers->get('location'));

        $user = new AccessEntity('logout-flow@example.test');
        $userAuth = $this->createMock(AccessAuthenticationServiceInterface::class);
        $userAuth->expects(self::exactly(2))->method('signOut')->with($user, self::isInstanceOf(Request::class));
        $signedIn = $this->service($user, authentication: $userAuth);
        $signedIn->signOut($this->requestWithSession('/access/signout'));
        $signedIn->switchUser($this->requestWithSession('/access/switch'));

        $views = [];
        $recovery = $this->service(views: $views);
        $requestRecovery = $recovery->requestRecovery($this->requestWithSession('/access/recover'));
        $resetRecovery = $recovery->resetRecovery($this->requestWithSession('/access/recover/reset'));
        self::assertInstanceOf(Response::class, $requestRecovery);
        self::assertInstanceOf(Response::class, $resetRecovery);
        self::assertSame(200, $requestRecovery->getStatusCode());
        self::assertSame(200, $resetRecovery->getStatusCode());
        self::assertSame(['access.recover_request', 'access.recover_reset'], array_map(static fn (AccessPageViewDTO $view): string => $view->view, $views));
    }

    public function testSubmittedRegistrationAndSignInHappyPaths(): void
    {
        $registrationData = new AccessRegistrationRequestDTO();
        $registrationData->email = 'new-user@example.test';
        $registrationData->displayName = 'New User';
        $registrationData->plainPassword = 'StrongPassword!123';
        $registration = $this->createMock(AccessRegistrationServiceInterface::class);
        $registration->expects(self::once())->method('register')->with($registrationData)->willReturn(new AccessEntity('new-user@example.test'));

        $registered = $this->service(registration: $registration, formData: $registrationData, submitted: true, valid: true)
            ->register($this->requestWithSession('/access/register'));
        self::assertInstanceOf(Response::class, $registered);
        self::assertSame(303, $registered->getStatusCode());

        $signInData = new AccessSignInRequestDTO();
        $signInData->emailAddress = 'new-user@example.test';
        $signInData->plainPassword = 'StrongPassword!123';
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('attemptPasswordSignIn')->willReturn(AccessSignInResultDTO::authenticated(new AccessEntity('new-user@example.test')));
        $signedIn = $this->service(authentication: $authentication, formData: $signInData, submitted: true, valid: true)
            ->signInSubmit($this->requestWithSession('/access/signin'));
        self::assertInstanceOf(Response::class, $signedIn);
        self::assertSame('/product/index', $signedIn->headers->get('location'));
    }

    public function testSubmittedSecondFactorAndRecoveryHappyPaths(): void
    {
        $user = new AccessEntity('pending@example.test');
        $authentication = $this->createMock(AccessAuthenticationServiceInterface::class);
        $authentication->method('getPendingSecondFactorUserId')->willReturn(17);
        $authentication->expects(self::once())->method('completePendingSecondFactor');
        $users = $this->createMock(AccessRepositoryInterface::class);
        $users->method('findById')->with(17)->willReturn($user);
        $secondFactor = $this->createMock(AccessSecondFactorServiceInterface::class);
        $secondFactor->method('verifyChallenge')->willReturn(true);
        $verification = new AccessVerificationCodeDTO();
        $verification->code = '654321';
        $completed = $this->service(authentication: $authentication, users: $users, secondFactor: $secondFactor, formData: $verification, submitted: true, valid: true)
            ->secondFactorChallenge($this->requestWithSession('/access/second-factor'));
        self::assertInstanceOf(Response::class, $completed);
        self::assertSame('/product/index', $completed->headers->get('location'));

        $recoveryData = new AccessRecoveryRequestDTO();
        $recoveryData->emailAddress = 'recover@example.test';
        $recovery = $this->createMock(AccessRecoveryServiceInterface::class);
        $recovery->expects(self::once())->method('requestPasswordRecovery')->willReturn(null);
        $requested = $this->service(recovery: $recovery, formData: $recoveryData, submitted: true, valid: true)
            ->requestRecovery($this->requestWithSession('/access/recover'));
        self::assertInstanceOf(Response::class, $requested);
        self::assertSame('/access.recover_reset', $requested->headers->get('location'));

        $resetData = new AccessRecoveryResetDTO();
        $resetData->emailAddress = 'recover@example.test';
        $resetData->code = '123456';
        $resetData->newPassword = 'ReplacementPassword!123';
        $reset = $this->createMock(AccessRecoveryServiceInterface::class);
        $reset->expects(self::once())->method('resetPassword')->willReturn(true);
        $resetResponse = $this->service(recovery: $reset, formData: $resetData, submitted: true, valid: true)
            ->resetRecovery($this->requestWithSession('/access/recover/reset'));
        self::assertInstanceOf(Response::class, $resetResponse);
        self::assertSame('/access.signin', $resetResponse->headers->get('location'));
    }

    /** @param list<AccessPageViewDTO> $views */
    private function service(
        ?AccessEntity $user = null,
        ?AccessAuthenticationServiceInterface $authentication = null,
        ?AccessRepositoryInterface $users = null,
        ?AccessPasskeyAuthenticationServiceInterface $passkeys = null,
        ?AccessRegistrationServiceInterface $registration = null,
        ?AccessSecondFactorServiceInterface $secondFactor = null,
        ?AccessRecoveryServiceInterface $recovery = null,
        mixed $formData = null,
        bool $submitted = false,
        bool $valid = false,
        array &$views = [],
    ): AccessSecurityFlowService {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn($submitted);
        $form->method('isValid')->willReturn($valid);
        $form->method('getData')->willReturn($formData);
        $form->method('createView')->willReturn(new FormView());
        $forms = $this->createMock(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(static fn (string $route): string => '/'.$route);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');
        $responder = $this->createMock(AccessPageResponderInterface::class);
        $responder->method('respond')->willReturnCallback(static function (AccessPageViewDTO $view) use (&$views): Response {
            $views[] = $view;

            return new Response('ok', $view->statusCode);
        });

        return new AccessSecurityFlowService(
            $security,
            $forms,
            $urls,
            $kernel,
            $registration ?? $this->createMock(AccessRegistrationServiceInterface::class),
            $authentication ?? $this->createMock(AccessAuthenticationServiceInterface::class),
            $users ?? $this->createMock(AccessRepositoryInterface::class),
            $secondFactor ?? $this->createMock(AccessSecondFactorServiceInterface::class),
            $recovery ?? $this->createMock(AccessRecoveryServiceInterface::class),
            $passkeys ?? $this->createMock(AccessPasskeyAuthenticationServiceInterface::class),
            new RateLimiterFactory([
                'id' => 'security_flow_coverage',
                'policy' => 'sliding_window',
                'limit' => 10,
                'interval' => '15 minutes',
            ], new InMemoryStorage()),
            new AccessPageViewFactory(),
            $responder,
        );
    }

    private function requestWithSession(string $path): Request
    {
        $request = Request::create($path, 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}
