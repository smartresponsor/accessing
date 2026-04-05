<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Service\SecondFactor\AccessingTotpEnrollmentService;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingSecondFactorController extends AbstractController
{
    #[Route('/second-factor', name: 'accessing_second_factor', methods: ['GET'])]
    public function index(AccessingTotpEnrollmentService $totpEnrollmentService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        $totpEnrollmentService->prepare($account);

        return $this->render('accessing/account/second_factor.html.twig', [
            'account' => $account,
            'provisioning_uri' => $totpEnrollmentService->buildProvisioningUri($account, 'Accessing'),
        ]);
    }

    #[Route('/second-factor/enable', name: 'accessing_second_factor_enable', methods: ['POST'])]
    public function enable(
        Request $request,
        AccessingTotpEnrollmentService $totpEnrollmentService,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('accessing_second_factor_enable', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var Account|null $account */
        $account = $this->getUser();
        if ($account instanceof Account) {
            $totpEnrollmentService->enable($account);
            $securityEventRecorder->record('second_factor.enabled', $account, [
                'channel' => 'totp',
            ]);
            $this->addFlash('success', 'Second factor enabled for the current account.');
        }

        return $this->redirectToRoute('accessing_second_factor');
    }

    #[Route('/second-factor/disable', name: 'accessing_second_factor_disable', methods: ['POST'])]
    public function disable(
        Request $request,
        AccessingTotpEnrollmentService $totpEnrollmentService,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('accessing_second_factor_disable', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var Account|null $account */
        $account = $this->getUser();
        if ($account instanceof Account) {
            $totpEnrollmentService->disable($account);
            $securityEventRecorder->record('second_factor.disabled', $account, [
                'channel' => 'totp',
            ]);
            $this->addFlash('warning', 'Second factor disabled and current TOTP secret cleared.');
        }

        return $this->redirectToRoute('accessing_second_factor');
    }
}
