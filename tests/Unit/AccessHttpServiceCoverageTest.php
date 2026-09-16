<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Context\AccessCurrentContext;
use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Factory\Rendering\AccessPageViewFactory;
use App\Accessing\ProviderInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\Service\Http\Access\AccessCredentialNoticeService;
use App\Accessing\Service\Http\Access\AccessCredentialRequestService;
use App\Accessing\Service\Http\Access\AccessCredentialResetService;
use App\Accessing\Service\Http\Access\AccessEmailVerificationService;
use App\Accessing\Service\Http\Access\AccessIndexService;
use App\Accessing\Service\Http\Access\AccessOperatorSecurityEventsService;
use App\Accessing\Service\Http\Access\AccessOperatorUserDetailService;
use App\Accessing\Service\Http\Access\AccessOperatorUsersService;
use App\Accessing\Service\Http\Access\AccessOwnerResolveService;
use App\Accessing\Service\Http\Access\AccessPhoneVerificationConfirmService;
use App\Accessing\Service\Http\Access\AccessPhoneVerificationRequestService;
use App\Accessing\Service\Http\Access\AccessRecoveryRequestService;
use App\Accessing\Service\Http\Access\AccessRecoveryResetService;
use App\Accessing\Service\Http\Access\AccessRegisterService;
use App\Accessing\Service\Http\Access\AccessSecurityEventsService;
use App\Accessing\Service\Http\Access\AccessSessionsService;
use App\Accessing\Service\Http\Access\AccessShowService;
use App\Accessing\Service\Http\Access\AccessSignInService;
use App\Accessing\Service\Http\Access\AccessSignOutService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AccessHttpServiceCoverageTest extends TestCase
{
    public function testSimplePageServicesProduceOwnedViews(): void
    {
        [$formFactory, $formView] = $this->formFactory();
        $factory = new AccessPageViewFactory();
        $views = [];
        $responder = $this->responder($views);
        $user = new AccessEntity('person@example.test', 'Person');

        $contextProvider = $this->createMock(AccessCurrentContextProviderInterface::class);
        $contextProvider->method('current')->willReturn(new AccessCurrentContext(42, 'person@example.test', 'Person', [], false, true, false));
        $userRepository = $this->createMock(AccessRepositoryInterface::class);
        $userRepository->method('findById')->with(42)->willReturn($user);
        $owner = new AccessOwnerResolveService($contextProvider, $userRepository);

        self::assertSame($user, $owner->requireAccess());
        self::assertResponseStatus(200, (new AccessRegisterService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessSignInService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessRecoveryRequestService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessRecoveryResetService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessCredentialRequestService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessCredentialNoticeService($factory, $responder))());
        self::assertResponseStatus(200, (new AccessCredentialResetService($formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessEmailVerificationService($owner, $formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessPhoneVerificationRequestService($owner, $formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessPhoneVerificationConfirmService($owner, $formFactory, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessSessionsService($owner, $factory, $responder))());

        self::assertSame([
            'access.register',
            'access.signin',
            'access.recover_request',
            'access.recover_reset',
            'access.reset_password_request',
            'access.reset_password_check_email',
            'access.reset_password_reset',
            'access.verify_email',
            'access.verify_phone_request',
            'access.verify_phone_confirm',
            'access.sessions',
        ], array_map(static fn (AccessPageViewDTO $view): string => $view->view, $views));
        self::assertSame($formView, $views[0]->parameters['form']);
    }

    public function testOperatorAndSecurityReadServicesUseRepositoryContracts(): void
    {
        $factory = new AccessPageViewFactory();
        $views = [];
        $responder = $this->responder($views);
        $user = new AccessEntity('operator-target@example.test');

        $users = $this->createMock(AccessRepositoryInterface::class);
        $users->expects(self::exactly(2))->method('findRecentUsers')->with(100)->willReturn([$user]);
        $users->method('findById')->with(7)->willReturn($user);

        $events = $this->createMock(AccessSecurityEventRepositoryInterface::class);
        $events->method('findRecentEvents')->willReturn([]);
        $events->method('findRecentEventsForUser')->with($user)->willReturn([]);

        self::assertResponseStatus(200, (new AccessIndexService($users, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessOperatorUsersService($users, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessSecurityEventsService($events, $factory, $responder))());
        self::assertResponseStatus(200, (new AccessOperatorSecurityEventsService($events, $factory, $responder))());

        $show = new AccessShowService($users, $events, $factory, $responder);
        self::assertResponseStatus(200, $show(7));
        self::assertResponseStatus(200, $show->showById(7));
        self::assertResponseStatus(200, (new AccessOperatorUserDetailService($users, $events, $factory, $responder))(7));

        self::assertSame([
            'access.operator_index',
            'access.operator_index',
            'access.security_event_index',
            'access.operator_security_event_index',
            'access.operator_detail',
            'access.operator_detail',
            'access.operator_detail',
        ], array_map(static fn (AccessPageViewDTO $view): string => $view->view, $views));
    }

    public function testOwnerAndOperatorTargetMissingPathsAreRejected(): void
    {
        $users = $this->createMock(AccessRepositoryInterface::class);
        $contextProvider = $this->createMock(AccessCurrentContextProviderInterface::class);
        $contextProvider->method('current')->willReturn(null);

        try {
            (new AccessOwnerResolveService($contextProvider, $users))->requireAccess();
            self::fail('Missing current access context must be rejected.');
        } catch (NotFoundHttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }

        $stringContextProvider = $this->createMock(AccessCurrentContextProviderInterface::class);
        $stringContextProvider->method('current')->willReturn(new AccessCurrentContext('external', 'person@example.test', null, [], false, false, false));
        try {
            (new AccessOwnerResolveService($stringContextProvider, $users))->requireAccess();
            self::fail('Non-integer local access identity must be rejected.');
        } catch (NotFoundHttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }

        $missingContextProvider = $this->createMock(AccessCurrentContextProviderInterface::class);
        $missingContextProvider->method('current')->willReturn(new AccessCurrentContext(99, 'missing@example.test', null, [], false, false, false));
        $users->method('findById')->willReturn(null);
        try {
            (new AccessOwnerResolveService($missingContextProvider, $users))->requireAccess();
            self::fail('Missing AccessEntity must be rejected.');
        } catch (NotFoundHttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }

        $show = new AccessShowService(
            $users,
            $this->createMock(AccessSecurityEventRepositoryInterface::class),
            new AccessPageViewFactory(),
            $this->createMock(AccessPageResponderInterface::class),
        );
        $this->expectException(NotFoundHttpException::class);
        $show->showById(404);
    }

    public function testSignOutRedirectsToAccessSignIn(): void
    {
        $response = (new AccessSignOutService())();

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/access/signin', $response->getTargetUrl());
    }

    private static function assertResponseStatus(int $expectedStatus, mixed $response): void
    {
        self::assertInstanceOf(Response::class, $response);
        self::assertSame($expectedStatus, $response->getStatusCode());
    }

    /** @return array{FormFactoryInterface, FormView} */
    private function formFactory(): array
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($view);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnSelf();
        $builder->method('getForm')->willReturn($form);

        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturn($form);
        $factory->method('createBuilder')->willReturn($builder);

        return [$factory, $view];
    }

    /** @param list<AccessPageViewDTO> $views */
    private function responder(array &$views): AccessPageResponderInterface
    {
        $responder = $this->createMock(AccessPageResponderInterface::class);
        $responder->method('respond')->willReturnCallback(static function (AccessPageViewDTO $view) use (&$views): Response {
            $views[] = $view;

            return new Response('ok');
        });

        return $responder;
    }
}
