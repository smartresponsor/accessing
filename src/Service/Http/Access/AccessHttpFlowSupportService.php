<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Entity\AccessEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Owns shared HTTP-flow mechanics for Accessing page flows.
 */
final readonly class AccessHttpFlowSupportService
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    public function currentUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
    }

    public function requireUser(): AccessEntity
    {
        $user = $this->currentUser();

        if (!$user instanceof AccessEntity) {
            throw new AccessDeniedHttpException('User access required.');
        }

        return $user;
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

    /** @param array<string, mixed> $parameters */
    public function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }
}
