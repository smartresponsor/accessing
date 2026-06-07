<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Dto\AccessPasswordChangeDto;
use App\Accessing\Dto\AccessPhoneVerificationRequestDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\Form\AccessPasswordChangeType;
use App\Accessing\Form\AccessPhoneVerificationRequestType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\UserSession\AccessUserSessionServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSurfaceBuilder
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    /**
     * Render home entrypoint for signed-in users and redirect guests to sign-in.
     */
    public function home(
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessHomeSurfaceContractFactory $surfaceContractFactory,
    ): Response|InterfaceSurfaceRenderableInterface {
        if (!$this->currentUser() instanceof AccessUserEntity) {
            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $user = $this->requireUser();

        return $surfaceContractFactory->create(
            $user,
            $securityEventRepository->findRecentEventsForUser($user, 8),
        );
    }

    /**
     * Verify user email ownership using a challenge code.
     */
    public function verifyEmail(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->completeEmailVerification($user, $data->code)) {
                $this->flash($request, 'success', 'Email verification completed.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'That email verification code is invalid or expired.');
        }

        $issuedChallenge = $verificationChallengeService->issueEmailVerification($user, $request);
        $this->flash($request, 'info', 'A fresh email verification code has been issued.');
        $this->addDemoCodeFlash($request, 'Email verification code', $issuedChallenge->plainCode);

        return $pageResponder->respond($pageViewFactory->verifyEmail(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Request a phone verification code and hand it to the provider.
     */
    public function requestPhone(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessPhoneVerificationRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPhoneVerificationRequestDto $data */
            $data = $form->getData();
            $issuedChallenge = $verificationChallengeService->issuePhoneVerification($user, $data->phoneNumber, $request);

            $this->flash($request, 'info', 'Phone verification code sent.');
            $this->addDemoCodeFlash($request, 'Phone verification code', $issuedChallenge->plainCode);

            return $this->redirectTo('accessing_verify_phone_confirm');
        }

        return $pageResponder->respond($pageViewFactory->requestPhoneVerification(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Confirm a phone verification challenge.
     */
    public function confirmPhone(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->completePhoneVerification($user, $data->code)) {
                $this->flash($request, 'success', 'Phone verification completed.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'That phone verification code is invalid or expired.');
        }

        return $pageResponder->respond($pageViewFactory->confirmPhoneVerification(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Complete second-factor enrollment.
     */
    public function secondFactor(
        Request $request,
        AccessSecondFactorServiceInterface $secondFactorService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $user = $this->requireUser();
        $enrollment = $secondFactorService->beginEnrollment($user);
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();
            $confirmedEnrollment = $secondFactorService->confirmEnrollment($user, $data->code);

            if (null !== $confirmedEnrollment) {
                $this->flash($request, 'success', 'Second factor is now enabled.');
                $this->flash($request, 'warning', 'Save the recovery codes shown on the page now. They will not be shown again.');

                return $pageResponder->respond($pageViewFactory->secondFactor(
                    $user,
                    $form->createView(),
                    $confirmedEnrollment,
                    true,
                    true,
                ));
            }

            $this->flash($request, 'danger', 'That authenticator code was not accepted.');
        }

        return $pageResponder->respond($pageViewFactory->secondFactor(
            $user,
            $form->createView(),
            $enrollment,
            $user->getSecondFactor()?->isEnabled() ?? false,
            false,
        ));
    }

    /**
     * Disable second-factor enrollment and recovery codes.
     */
    public function disableSecondFactorNotAllowed(): Response
    {
        return new Response('', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function disableSecondFactor(
        Request $request,
        AccessSecondFactorServiceInterface $secondFactorService,
    ): Response|InterfaceSurfaceRenderableInterface {
        $secondFactorService->disableSecondFactor($this->requireUser());
        $this->flash($request, 'info', 'Second factor has been disabled.');

        return $this->redirectTo('accessing_second_factor');
    }

    /**
     * Render session management page.
     */
    public function sessions(
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->sessions($this->requireUser()));
    }

    /**
     * Invalidate all active sessions except current one.
     */
    public function invalidateOtherSessions(
        Request $request,
        AccessUserSessionServiceInterface $userSessionService,
    ): Response|InterfaceSurfaceRenderableInterface {
        $invalidatedCount = $userSessionService->invalidateOtherSessions($this->requireUser(), $request->getSession());
        $this->flash($request, 'info', sprintf('%d other session(s) invalidated.', $invalidatedCount));

        return $this->redirectTo('accessing_sessions');
    }

    /**
     * Render recent security events for current user.
     */
    public function securityEvents(
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->securityEvents(
            $securityEventRepository->findRecentEventsForUser($this->requireUser()),
        ));
    }

    /**
     * Change user password after current-password verification.
     */
    public function password(
        Request $request,
        AccessCredentialServiceInterface $credentialService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPasswordChangeDto $data */
            $data = $form->getData();

            if (!$credentialService->verifyPassword($user, $data->currentPassword)) {
                $this->flash($request, 'danger', 'The current password is incorrect.');
            } else {
                $credentialService->changePassword($user, $data->newPassword);
                $this->flash($request, 'success', 'Password updated.');

                return $this->redirectTo('accessing_home');
            }
        }

        return $pageResponder->respond($pageViewFactory->password($user, $form->createView()));
    }

    private function requireUser(): AccessUserEntity
    {
        $user = $this->currentUser();

        if (!$user instanceof AccessUserEntity) {
            throw new AccessDeniedHttpException('User access required.');
        }

        return $user;
    }

    private function currentUser(): ?AccessUserEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessUserEntity ? $user : null;
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
}
