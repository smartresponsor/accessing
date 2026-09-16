<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Contract\Surface\AccessHomeSurfaceContract;
use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Factory\Rendering\AccessPageViewFactory;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\Service\Http\Access\AccessSurfaceFlowService;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccessSurfaceFlowServiceTest extends TestCase
{
    public function testHomeRedirectsGuestAndBuildsAuthenticatedSurface(): void
    {
        $guest = $this->service(null)->home();
        self::assertInstanceOf(Response::class, $guest);
        self::assertSame(302, $guest->getStatusCode());
        self::assertSame('/access.signin', $guest->headers->get('location'));

        $user = new AccessEntity('surface@example.test', 'Surface User');
        $events = $this->createMock(AccessSecurityEventRepositoryInterface::class);
        $events->expects(self::once())->method('findRecentEventsForUser')->with($user, 8)->willReturn(['event']);

        $surface = $this->service($user, events: $events)->home();
        self::assertInstanceOf(AccessHomeSurfaceContract::class, $surface);
        self::assertSame('access.overview', $surface->view);
        self::assertSame(['event'], $surface->slots['events']);
        self::assertSame($user, $surface->slots['user']);
    }

    public function testVerificationAndPhonePagesRenderForAuthenticatedUser(): void
    {
        $user = new AccessEntity('verify@example.test');
        $views = [];
        $verification = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $verification->expects(self::once())->method('resendEmailVerification')->with($user)->willReturn(null);
        $service = $this->service($user, verification: $verification, views: $views);

        $verifyEmail = $service->verifyEmail($this->requestWithSession());
        $requestPhone = $service->requestPhone($this->requestWithSession());
        $confirmPhone = $service->confirmPhone($this->requestWithSession());
        self::assertInstanceOf(Response::class, $verifyEmail);
        self::assertInstanceOf(Response::class, $requestPhone);
        self::assertInstanceOf(Response::class, $confirmPhone);
        self::assertSame(200, $verifyEmail->getStatusCode());
        self::assertSame(200, $requestPhone->getStatusCode());
        self::assertSame(200, $confirmPhone->getStatusCode());
        self::assertSame(
            ['access.verify_email', 'access.verify_phone_request', 'access.verify_phone_confirm'],
            array_map(static fn (AccessPageViewDTO $view): string => $view->view, $views),
        );
    }

    public function testSecondFactorReadDisableAndMethodGuard(): void
    {
        $user = new AccessEntity('mfa@example.test');
        $views = [];
        $secondFactor = $this->createMock(AccessSecondFactorServiceInterface::class);
        $enrollment = new AccessSecondFactorEnrollmentDTO('secret', 'otpauth://totp/example', ['recovery']);
        $secondFactor->expects(self::once())->method('beginEnrollment')->with($user)->willReturn($enrollment);
        $secondFactor->expects(self::once())->method('disableSecondFactor')->with($user);
        $service = $this->service($user, secondFactor: $secondFactor, views: $views);

        $secondFactorPage = $service->secondFactor($this->requestWithSession());
        self::assertInstanceOf(Response::class, $secondFactorPage);
        self::assertSame(200, $secondFactorPage->getStatusCode());
        self::assertSame('access.second_factor', $views[0]->view);
        self::assertSame($enrollment, $views[0]->parameters['enrollment']);

        $disabled = $service->disableSecondFactor($this->requestWithSession());
        self::assertInstanceOf(Response::class, $disabled);
        self::assertSame(302, $disabled->getStatusCode());
        self::assertSame('/access.second_factor', $disabled->headers->get('location'));
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $service->disableSecondFactorNotAllowed()->getStatusCode());
    }

    public function testSessionSecurityAndPasswordPagesPlusInvalidation(): void
    {
        $user = new AccessEntity('session@example.test');
        $views = [];
        $events = $this->createMock(AccessSecurityEventRepositoryInterface::class);
        $events->expects(self::once())->method('findRecentEventsForUser')->with($user)->willReturn(['security-event']);
        $sessions = $this->createMock(AccessSessionServiceInterface::class);
        $sessions->expects(self::once())->method('invalidateOtherSessions')->willReturn(2);
        $service = $this->service($user, events: $events, sessions: $sessions, views: $views);

        $sessionsPage = $service->sessions();
        $securityEventsPage = $service->securityEvents();
        $passwordPage = $service->password($this->requestWithSession());
        self::assertInstanceOf(Response::class, $sessionsPage);
        self::assertInstanceOf(Response::class, $securityEventsPage);
        self::assertInstanceOf(Response::class, $passwordPage);
        self::assertSame(200, $sessionsPage->getStatusCode());
        self::assertSame(200, $securityEventsPage->getStatusCode());
        self::assertSame(200, $passwordPage->getStatusCode());
        self::assertSame(
            ['access.sessions', 'access.security_event_index', 'access.password'],
            array_map(static fn (AccessPageViewDTO $view): string => $view->view, $views),
        );

        $request = $this->requestWithSession();
        $response = $service->invalidateOtherSessions($request);
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/access.sessions', $response->headers->get('location'));
        $session = $request->getSession();
        self::assertInstanceOf(Session::class, $session);
        self::assertSame(['2 other session(s) invalidated.'], $session->getFlashBag()->peek('info'));
    }

    public function testAuthenticatedSurfaceMethodsRejectGuest(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->service(null)->sessions();
    }

    /** @param list<AccessPageViewDTO> $views */
    private function service(
        ?AccessEntity $user,
        ?AccessSecurityEventRepositoryInterface $events = null,
        ?AccessVerificationChallengeServiceInterface $verification = null,
        ?AccessSecondFactorServiceInterface $secondFactor = null,
        ?AccessSessionServiceInterface $sessions = null,
        ?AccessCredentialServiceInterface $credentials = null,
        array &$views = [],
    ): AccessSurfaceFlowService {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(static fn (string $route): string => '/'.$route);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');

        $events ??= $this->createMock(AccessSecurityEventRepositoryInterface::class);
        $verification ??= $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $secondFactor ??= $this->createMock(AccessSecondFactorServiceInterface::class);
        $sessions ??= $this->createMock(AccessSessionServiceInterface::class);
        $credentials ??= $this->createMock(AccessCredentialServiceInterface::class);

        $responder = $this->createMock(AccessPageResponderInterface::class);
        $responder->method('respond')->willReturnCallback(static function (AccessPageViewDTO $view) use (&$views): Response {
            $views[] = $view;

            return new Response('ok');
        });

        return new AccessSurfaceFlowService(
            $security,
            $formFactory,
            $urls,
            $kernel,
            $events,
            new AccessHomeSurfaceContractFactory('Accessing'),
            $verification,
            $secondFactor,
            $sessions,
            $credentials,
            new AccessPageViewFactory(),
            $responder,
        );
    }

    private function requestWithSession(): Request
    {
        $request = Request::create('/access');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}
