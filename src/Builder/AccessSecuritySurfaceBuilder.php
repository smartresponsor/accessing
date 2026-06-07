<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Dto\AccessRecoveryRequestDto;
use App\Accessing\Dto\AccessRecoveryResetDto;
use App\Accessing\Dto\AccessUserRegistrationRequest;
use App\Accessing\Dto\AccessUserSignInRequestDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Form\AccessRecoveryRequestType;
use App\Accessing\Form\AccessRecoveryResetType;
use App\Accessing\Form\AccessUserRegistrationType;
use App\Accessing\Form\AccessUserSignInType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessUserRepositoryInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\User\AccessUserAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\User\AccessUserRegistrationServiceInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSecuritySurfaceBuilder
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    public function register(
        Request $request,
        AccessUserRegistrationServiceInterface $userRegistrationService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessUserEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_up', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessUserRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessUserRegistrationRequest $data */
            $data = $form->getData();

            try {
                $userRegistrationService->register($data);
                $this->flash($request, 'success', 'Registration complete. Verify your email address to finish activation.');

                return $this->redirectTo('interfacing_welcome_sign_in');
            } catch (\DomainException $exception) {
                $this->flash($request, 'danger', $exception->getMessage());
            }
        }

        return $pageResponder->respond($pageViewFactory->register($form->createView()));
    }

    public function signIn(
        Request $request,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessUserEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessUserSignInType::class);

        return $pageResponder->respond($pageViewFactory->signIn($form->createView()));
    }

    public function signInTrailingSlash(): Response
    {
        return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }

    public function signInSubmit(
        Request $request,
        AccessUserAuthenticationServiceInterface $userAuthenticationService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessUserEntity) {
            return $this->redirectTo('accessing_home');
        }

        $form = $this->formFactory->create(AccessUserSignInType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessUserSignInRequestDto $data */
            $data = $form->getData();
            $result = $userAuthenticationService->attemptPasswordSignIn(
                $data->emailAddress,
                $data->plainPassword,
                $request,
            );

            if ($result->authenticated) {
                return $this->redirectTo('accessing_home');
            }

            if ($result->requiresSecondFactor) {
                $this->flash($request, 'info', 'Enter your authenticator or recovery code to finish signing in.');

                return $this->redirectTo('accessing_sign_in_second_factor');
            }

            $this->flash($request, 'danger', $result->message);
        }

        return $pageResponder->respond($pageViewFactory->signIn($form->createView()));
    }

    public function secondFactorChallenge(
        Request $request,
        AccessUserRepositoryInterface $userRepository,
        AccessUserAuthenticationServiceInterface $userAuthenticationService,
        AccessSecondFactorServiceInterface $secondFactorService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $pendingUserId = $userAuthenticationService->getPendingSecondFactorUserId($request->getSession());

        if (null === $pendingUserId) {
            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $user = $userRepository->findById($pendingUserId);

        if (!$user instanceof AccessUserEntity) {
            $userAuthenticationService->clearPendingSecondFactor($request->getSession());

            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($secondFactorService->verifyChallenge($user, $data->code)) {
                $userAuthenticationService->completePendingSecondFactor($user, $request);
                $this->flash($request, 'success', 'Signed in successfully.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'The second factor code was not accepted.');
        }

        return $pageResponder->respond($pageViewFactory->secondFactorChallenge($user, $form->createView()));
    }

    public function signOut(
        Request $request,
        AccessUserAuthenticationServiceInterface $userAuthenticationService,
    ): Response|InterfaceSurfaceRenderableInterface {
        $userAuthenticationService->signOut(
            $this->getUser() instanceof AccessUserEntity ? $this->getUser() : null,
            $request,
        );

        return $this->redirectTo('interfacing_welcome_sign_in');
    }

    public function switchUser(
        Request $request,
        AccessUserAuthenticationServiceInterface $userAuthenticationService,
    ): Response|InterfaceSurfaceRenderableInterface {
        $userAuthenticationService->signOut(
            $this->getUser() instanceof AccessUserEntity ? $this->getUser() : null,
            $request,
        );

        $this->flash($request, 'info', 'Signed out. Use another user to continue.');

        return $this->redirectTo('interfacing_welcome_sign_in');
    }

    public function requestRecovery(
        Request $request,
        AccessRecoveryServiceInterface $recoveryService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $form = $this->formFactory->create(AccessRecoveryRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryRequestDto $data */
            $data = $form->getData();
            $issuedChallenge = $recoveryService->requestPasswordRecovery($data->emailAddress, $request);
            $this->flash($request, 'info', 'If an user exists, a password recovery code has been issued.');

            if (null !== $issuedChallenge) {
                $this->addDemoCodeFlash($request, 'Password recovery code', $issuedChallenge->plainCode);
            }

            return $this->redirectTo('accessing_recover_reset');
        }

        return $pageResponder->respond($pageViewFactory->requestRecovery($form->createView()));
    }

    public function resetRecovery(
        Request $request,
        AccessRecoveryServiceInterface $recoveryService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $form = $this->formFactory->create(AccessRecoveryResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryResetDto $data */
            $data = $form->getData();

            if ($recoveryService->resetPassword(
                $data->emailAddress,
                $data->code,
                $data->newPassword,
            )) {
                $this->flash($request, 'success', 'Password recovery completed. You can now sign in.');

                return $this->redirectTo('interfacing_welcome_sign_in');
            }

            $this->flash($request, 'danger', 'Password recovery failed. Check the email address and recovery code.');
        }

        return $pageResponder->respond($pageViewFactory->resetRecovery($form->createView()));
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add($type, $message);
    }

    private function addDemoCodeFlash(Request $request, string $label, string $code): void
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $this->flash($request, 'secondary', sprintf('%s: %s', $label, $code));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }

    private function getUser(): ?AccessUserEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessUserEntity ? $user : null;
    }
}
