<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\ServiceInterface\Verification\AccessingEmailVerificationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingEmailVerificationController extends AbstractController
{
    #[Route('/verification/email/request', name: 'accessing_email_verification_request', methods: ['GET'])]
    public function request(AccessingEmailVerificationServiceInterface $emailVerificationService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        $challenge = $emailVerificationService->issueChallenge($account);

        $this->addFlash('info', sprintf('Email verification challenge created. Demo token: %s', $challenge->getToken()));

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
