<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Dto\AccessPasswordChangeDto;
use App\Accessing\Dto\AccessPhoneVerificationRequestDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\Form\Access\AccessPasswordChangeType;
use App\Accessing\Form\Access\AccessPhoneVerificationRequestType;
use App\Accessing\Form\Access\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSurfaceFlowService
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessHomeSurfaceContractFactory $surfaceContractFactory,
        private AccessVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessSecondFactorServiceInterface $secondFactorService,
        private AccessSessionServiceInterface $userSessionService,
        private AccessCredentialServiceInterface $credentialService,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Render home entrypoint for signed in users and redirect guests to sign in.
     */
    public function home(): Response|InterfaceTemplateRenderableInterface
    {
        if (!$this->currentUser() instanceof AccessEntity) {
            return $this->redirectTo('access.signin');
        }

        $user = $this->requireUser();

        return $this->surfaceContractFactory->create(
            $user,
            $this->securityEventRepository->findRecentEventsForUser($user, 8),
        );
    }

    /**
     * Verify user email ownership using a challenge code.
     */
    public function verifyEmail(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($this->verificationChallengeService->completeEmailVerification($user, $data->code)) {
                $this->flash($request, 'success', 'Email verification completed.');

                return $this->redirectTo('access.index');
            }

            $this->flash($request, 'danger', 'That email verification code is invalid or expired.');
        }

        $issuedChallenge = $this->verificationChallengeService->resendEmailVerification($user, $request);

        if (null === $issuedChallenge) {
            $this->flash($request, 'warning', 'Too many verification resend attempts. Please wait before trying again.');
        } else {
            $this->flash($request, 'info', 'A fresh email verification code has been issued.');
            $this->addDemoCodeFlash($request, 'Email verification code', $issuedChallenge->plainCode);
        }

        return $this->pageResponder->respond($this->pageViewFactory->verifyEmail(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Request a phone verification code and hand it to the provider.
     */
    public function requestPhone(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessPhoneVerificationRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPhoneVerificationRequestDto $data */
            $data = $form->getData();
            $issuedChallenge = $this->verificationChallengeService->issuePhoneVerification($user, $data->phoneNumber, $request);

            $this->flash($request, 'info', 'Phone verification code sent.');
            $this->addDemoCodeFlash($request, 'Phone verification code', $issuedChallenge->plainCode);

            return $this->redirectTo('access.verify_phone_confirm');
        }

        return $this->pageResponder->respond($this->pageViewFactory->requestPhoneVerification(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Confirm a phone verification challenge.
     */
    public function confirmPhone(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($this->verificationChallengeService->completePhoneVerification($user, $data->code)) {
                $this->flash($request, 'success', 'Phone verification completed.');

                return $this->redirectTo('access.index');
            }

            $this->flash($request, 'danger', 'That phone verification code is invalid or expired.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->confirmPhoneVerification(
            $user,
            $form->createView(),
        ));
    }

    /**
     * Complete second-factor enrollment.
     */
    public function secondFactor(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->requireUser();
        $enrollment = $this->secondFactorService->beginEnrollment($user);
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();
            $confirmedEnrollment = $this->secondFactorService->confirmEnrollment($user, $data->code);

            if (null !== $confirmedEnrollment) {
                $this->flash($request, 'success', 'Second factor is now enabled.');
                $this->flash($request, 'warning', 'Save the recovery codes shown on the page now. They will not be shown again.');

                return $this->pageResponder->respond($this->pageViewFactory->secondFactor(
                    $user,
                    $form->createView(),
                    $confirmedEnrollment,
                    true,
                    true,
                ));
            }

            $this->flash($request, 'danger', 'That authenticator code was not accepted.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->secondFactor(
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

    public function disableSecondFactor(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->secondFactorService->disableSecondFactor($this->requireUser());
        $this->flash($request, 'info', 'Second factor has been disabled.');

        return $this->redirectTo('access.second_factor');
    }

    /**
     * Render session management page.
     */
    public function sessions(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->sessions($this->requireUser()));
    }

    /**
     * Invalidate all active sessions except current one.
     */
    public function invalidateOtherSessions(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $invalidatedCount = $this->userSessionService->invalidateOtherSessions($this->requireUser(), $request->getSession());
        $this->flash($request, 'info', sprintf('%d other session(s) invalidated.', $invalidatedCount));

        return $this->redirectTo('access.sessions');
    }

    /**
     * Render recent security events for current user.
     */
    public function securityEvents(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->securityEvents(
            $this->securityEventRepository->findRecentEventsForUser($this->requireUser()),
        ));
    }

    /**
     * Change user password after current-password verification.
     */
    public function password(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->requireUser();
        $form = $this->formFactory->create(AccessPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPasswordChangeDto $data */
            $data = $form->getData();

            if (!$this->credentialService->verifyPassword($user, $data->currentPassword)) {
                $this->flash($request, 'danger', 'The current password is incorrect.');
            } else {
                try {
                    $this->credentialService->changePassword($user, $data->newPassword);
                } catch (AccessCompromisedPasswordException $exception) {
                    $this->flash($request, 'danger', $exception->getMessage());

                    return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
                } catch (AccessPasswordSafetyUnavailableException $exception) {
                    $this->flash($request, 'warning', $exception->getMessage());

                    return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
                }

                $this->flash($request, 'success', 'Password updated.');

                return $this->redirectTo('access.index');
            }
        }

        return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
    }

    private function requireUser(): AccessEntity
    {
        $user = $this->currentUser();

        if (!$user instanceof AccessEntity) {
            throw new AccessDeniedHttpException('User access required.');
        }

        return $user;
    }

    private function currentUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
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
