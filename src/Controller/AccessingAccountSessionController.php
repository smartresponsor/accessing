<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\ServiceInterface\AccountSession\AccessingAccountSessionViewProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingAccountSessionController extends AbstractController
{
    /**
     * Render the latest sessions for the authenticated account.
     */
    #[Route('/sessions', name: 'accessing_account_session_index', methods: ['GET'])]
    public function __invoke(AccessingAccountSessionViewProviderInterface $accountSessionViewProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        $sessions = $accountSessionViewProvider->listRecentForAccount($account, 50);

        return $this->render('accessing/account/session_index.html.twig', [
            'sessions' => $sessions,
        ]);
    }
}
