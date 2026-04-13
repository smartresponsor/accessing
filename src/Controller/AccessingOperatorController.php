<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Controller;

use App\RepositoryInterface\AccountRepositoryInterface;
use App\RepositoryInterface\SecurityEventRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPPORT')]
#[Route('/operator', name: 'accessing_operator_')]
final class AccessingOperatorController extends AbstractController
{
    #[Route('/accounts', name: 'accounts', methods: ['GET'])]
    public function accounts(AccountRepositoryInterface $accountRepository): Response
    {
        return $this->render('accessing/account/operator_index.html.twig', [
            'accounts' => $accountRepository->findRecentAccounts(100),
        ]);
    }

    #[Route('/accounts/{id}', name: 'account_detail', methods: ['GET'])]
    public function accountDetail(
        int $id,
        AccountRepositoryInterface $accountRepository,
        SecurityEventRepositoryInterface $securityEventRepository,
    ): Response {
        $account = $accountRepository->findById($id);

        if ($account === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('accessing/account/operator_detail.html.twig', [
            'account' => $account,
            'events' => $securityEventRepository->findRecentEventsForAccount($account),
        ]);
    }

    #[Route('/security-events', name: 'security_events', methods: ['GET'])]
    public function securityEvents(SecurityEventRepositoryInterface $securityEventRepository): Response
    {
        return $this->render('accessing/security_event/operator_index.html.twig', [
            'events' => $securityEventRepository->findRecentEvents(150),
        ]);
    }
}
