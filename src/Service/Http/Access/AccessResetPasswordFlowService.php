<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Form\Access\AccessChangePasswordType;
use App\Accessing\Form\Access\AccessResetPasswordRequestType;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class AccessResetPasswordFlowService
{
    private const string RESET_PASSWORD_TOKEN_SESSION_KEY = 'accessing_reset_password_token';

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private AccessCredentialServiceInterface $credentialService,
        private AccessRepositoryInterface $userRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
        private RateLimiterFactory $accessingForgotPasswordLimiter,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Accept a password reset request and issue a reset token when user exists.
     */
    public function request(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->create(AccessResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailData = $form->get('email')->getData();
            $email = is_string($emailData) ? mb_strtolower(trim($emailData)) : '';
            $limiterKey = sprintf('%s|%s', $email, $request->getClientIp() ?? 'unknown');

            if (!$this->accessingForgotPasswordLimiter->create($limiterKey)->consume()->isAccepted()) {
                return $this->redirectTo('access.reset_password_check_email');
            }

            $user = $this->userRepository->findOneByEmailAddress($email);

            if ($user instanceof AccessEntity) {
                try {
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                    $this->securityEventService->record(
                        AccessSecurityEventType::RecoveryRequested,
                        AccessSecurityEventSeverity::Warning,
                        $user,
                        $request,
                    );

                    if (in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)) {
                        $this->flash($request, 'info', sprintf(
                            'Owner-oriented preview link: %s',
                            $this->urlGenerator->generate(
                                'access.reset_password_reset',
                                ['token' => $resetToken->getToken()],
                                UrlGeneratorInterface::ABSOLUTE_URL,
                            )
                        ));
                    }
                } catch (ResetPasswordExceptionInterface) {
                    $this->flash($request, 'warning', 'A reset request could not be created right now.');
                }
            }

            return $this->redirectTo('access.reset_password_check_email');
        }

        return $this->pageResponder->respond($this->pageViewFactory->resetPasswordRequest($form->createView()));
    }

    public function checkEmail(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->resetPasswordCheckEmail());
    }

    /**
     * Validate a reset token and update user password when submitted data is valid.
     */
    public function reset(Request $request, ?string $token = null): Response|InterfaceTemplateRenderableInterface
    {
        $session = $request->getSession();

        if (null !== $token && '' !== trim($token)) {
            $session->set(self::RESET_PASSWORD_TOKEN_SESSION_KEY, trim($token));

            return $this->redirectTo('access.reset_password_reset');
        }

        $tokenData = $session->get(self::RESET_PASSWORD_TOKEN_SESSION_KEY, '');
        $token = is_string($tokenData) ? $tokenData : '';
        if ('' === $token) {
            return $this->redirectTo('access.reset_password_request');
        }

        $form = $this->formFactory->create(AccessChangePasswordType::class);
        if ('GET' === $request->getMethod()) {
            return $this->pageResponder->respond($this->pageViewFactory->resetPassword($form->createView()));
        }

        try {
            /** @var AccessEntity $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $this->flash($request, 'danger', 'Invalid or expired reset token.');

            return $this->redirectTo('access.reset_password_request');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPasswordData = $form->get('plainPassword')->getData();
            $plainPassword = is_string($plainPasswordData) ? $plainPasswordData : '';

            try {
                $this->credentialService->changePassword($user, $plainPassword);
            } catch (AccessCompromisedPasswordException $exception) {
                $this->flash($request, 'danger', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetPassword($form->createView()));
            } catch (AccessPasswordSafetyUnavailableException $exception) {
                $this->flash($request, 'warning', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetPassword($form->createView()));
            }

            $this->resetPasswordHelper->removeResetRequest($token);
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $this->userRepository->save($user, true);

            $this->securityEventService->record(
                AccessSecurityEventType::RecoveryCompleted,
                AccessSecurityEventSeverity::Warning,
                $user,
                $request,
            );

            $this->flash($request, 'success', 'Password changed successfully.');

            return $this->redirectTo('access.signin');
        }

        return $this->pageResponder->respond($this->pageViewFactory->resetPassword($form->createView()));
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add($type, $message);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }
}
