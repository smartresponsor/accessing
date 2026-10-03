<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\DTO\AccessPasswordChangeDTO;
use App\Accessing\DTO\AccessPhoneVerificationRequestDTO;
use App\Accessing\DTO\AccessVerificationCodeDTO;
use App\Accessing\Entity\Access\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\Form\AccessPasswordChangeType;
use App\Accessing\Form\AccessPhoneVerificationRequestType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines the surface flow service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSurfaceFlowService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessWebFlowSupportService $webFlowSupport,
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
        if (!$this->webFlowSupport->currentUser() instanceof AccessEntity) {
            return $this->webFlowSupport->redirectTo('access.signin');
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
        $form = $this->webFlowSupport->createForm(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDTO $data */
            $data = $form->getData();

            if ($this->verificationChallengeService->completeEmailVerification($user, $data->code)) {
                $this->webFlowSupport->flash($request, 'success', 'Email verification completed.');

                return $this->webFlowSupport->redirectTo('access.index');
            }

            $this->webFlowSupport->flash($request, 'danger', 'That email verification code is invalid or expired.');
        }

        $issuedChallenge = $this->verificationChallengeService->resendEmailVerification($user, $request);

        if (null === $issuedChallenge) {
            $this->webFlowSupport->flash($request, 'warning', 'Too many verification resend attempts. Please wait before trying again.');
        } else {
            $this->webFlowSupport->flash($request, 'info', 'A fresh email verification code has been issued.');
            $this->webFlowSupport->addDemoCodeFlash($request, 'Email verification code', $issuedChallenge->plainCode);
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
        $form = $this->webFlowSupport->createForm(AccessPhoneVerificationRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPhoneVerificationRequestDTO $data */
            $data = $form->getData();
            $issuedChallenge = $this->verificationChallengeService->issuePhoneVerification($user, $data->phoneNumber, $request);

            $this->webFlowSupport->flash($request, 'info', 'Phone verification code sent.');
            $this->webFlowSupport->addDemoCodeFlash($request, 'Phone verification code', $issuedChallenge->plainCode);

            return $this->webFlowSupport->redirectTo('access.verify_phone_confirm');
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
        $form = $this->webFlowSupport->createForm(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDTO $data */
            $data = $form->getData();

            if ($this->verificationChallengeService->completePhoneVerification($user, $data->code)) {
                $this->webFlowSupport->flash($request, 'success', 'Phone verification completed.');

                return $this->webFlowSupport->redirectTo('access.index');
            }

            $this->webFlowSupport->flash($request, 'danger', 'That phone verification code is invalid or expired.');
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
        $form = $this->webFlowSupport->createForm(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDTO $data */
            $data = $form->getData();
            $confirmedEnrollment = $this->secondFactorService->confirmEnrollment($user, $data->code);

            if (null !== $confirmedEnrollment) {
                $this->webFlowSupport->flash($request, 'success', 'Second factor is now enabled.');
                $this->webFlowSupport->flash($request, 'warning', 'Save the recovery codes shown on the page now. They will not be shown again.');

                return $this->pageResponder->respond($this->pageViewFactory->secondFactor(
                    $user,
                    $form->createView(),
                    $confirmedEnrollment,
                    true,
                    true,
                ));
            }

            $this->webFlowSupport->flash($request, 'danger', 'That authenticator code was not accepted.');
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

    /**
     * Executes the disable second factor operation within the canonical Accessing component workflow.
     */
    public function disableSecondFactor(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->secondFactorService->disableSecondFactor($this->requireUser());
        $this->webFlowSupport->flash($request, 'info', 'Second factor has been disabled.');

        return $this->webFlowSupport->redirectTo('access.second_factor');
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
        $this->webFlowSupport->flash($request, 'info', sprintf('%d other session(s) invalidated.', $invalidatedCount));

        return $this->webFlowSupport->redirectTo('access.sessions');
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
        $form = $this->webFlowSupport->createForm(AccessPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPasswordChangeDTO $data */
            $data = $form->getData();

            if (!$this->credentialService->verifyPassword($user, $data->currentPassword)) {
                $this->webFlowSupport->flash($request, 'danger', 'The current password is incorrect.');
            } else {
                try {
                    $this->credentialService->changePassword($user, $data->newPassword);
                } catch (AccessCompromisedPasswordException $exception) {
                    $this->webFlowSupport->flash($request, 'danger', $exception->getMessage());

                    return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
                } catch (AccessPasswordSafetyUnavailableException $exception) {
                    $this->webFlowSupport->flash($request, 'warning', $exception->getMessage());

                    return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
                }

                $this->webFlowSupport->flash($request, 'success', 'Password updated.');

                return $this->webFlowSupport->redirectTo('access.index');
            }
        }

        return $this->pageResponder->respond($this->pageViewFactory->password($user, $form->createView()));
    }

    /**
     * Executes the require user operation within the canonical Accessing component workflow.
     */
    private function requireUser(): AccessEntity
    {
        $user = $this->webFlowSupport->currentUser();

        if (!$user instanceof AccessEntity) {
            throw new AccessDeniedHttpException('User access required.');
        }

        return $user;
    }
}
