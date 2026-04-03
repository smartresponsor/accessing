<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\ServiceInterface\Verification\AccessingEmailVerificationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccessingEmailVerificationController extends AbstractController
{
    #[Route('/verification/email/request', name: 'accessing_email_verification_request', methods: ['POST'])]
    public function request(Request $request, AccessingEmailVerificationServiceInterface $emailVerificationService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('accessing_email_verification_request', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        if (null !== $account->getEmailVerifiedAt()) {
            $this->addFlash('success', 'Email is already verified for the current account.');

            return $this->redirectToRoute('accessing_dashboard');
        }

        $challenge = $emailVerificationService->issueChallenge($account);

        $this->addFlash('info', sprintf(
            'Email verification preview link: %s',
            $this->generateUrl(
                'accessing_email_verification_confirm',
                ['token' => $challenge->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
        ));

        return $this->redirectToRoute('accessing_dashboard');
    }

    #[Route('/verification/email/{token}', name: 'accessing_email_verification_confirm', methods: ['GET'])]
    public function confirm(string $token, AccessingEmailVerificationServiceInterface $emailVerificationService): Response
    {
        $account = $emailVerificationService->confirmChallenge($token);

        if (!$account instanceof Account) {
            throw $this->createNotFoundException('Verification challenge not found or expired.');
        }

        return $this->render('accessing/account/verify_email_success.html.twig', [
            'account' => $account,
        ]);
    }
}
