<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Entity\Access\AccessEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Centralizes framework-level HTTP flow mechanics shared by Accessing web flows.
 *
 * Business decisions remain in the owning flow services; this service only exposes
 * Symfony request/session, form, routing, current-user, and environment mechanics.
 */
final readonly class AccessWebFlowSupportService
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    /** @param class-string $type */
    public function createForm(string $type): FormInterface
    {
        return $this->formFactory->create($type);
    }

    public function currentUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }

    public function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add($type, $message);
    }

    public function addDemoCodeFlash(Request $request, string $label, string $code): void
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $this->flash($request, 'secondary', sprintf('%s: %s', $label, $code));
    }
}
