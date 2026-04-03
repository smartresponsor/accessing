<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AccountRepository;
use App\Repository\SecurityEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPPORT')]
#[Route('/operator', name: 'accessing_operator_')]
final class AccessingOperatorController extends AbstractController
{
    #[Route('/accounts', name: 'accounts', methods: ['GET'])]
    public function accounts(AccountRepository $accountRepository): Response
    {
        return $this->render('accessing/account/operator_index.html.twig', [
            'accounts' => $accountRepository->findRecentAccounts(100),
        ]);
    }

    #[Route('/accounts/{id}', name: 'account_detail', methods: ['GET'])]
    public function accountDetail(
        int $id,
        AccountRepository $accountRepository,
        SecurityEventRepository $securityEventRepository,
    ): Response {
        $account = $accountRepository->find($id);

        if ($account === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('accessing/account/operator_detail.html.twig', [
            'account' => $account,
            'events' => $securityEventRepository->findRecentEventsForAccount($account),
        ]);
    }

    #[Route('/security-events', name: 'security_events', methods: ['GET'])]
    public function securityEvents(SecurityEventRepository $securityEventRepository): Response
    {
        return $this->render('accessing/security_event/operator_index.html.twig', [
            'events' => $securityEventRepository->findRecentEvents(150),
        ]);
    }
}
