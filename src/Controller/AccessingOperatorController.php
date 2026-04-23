<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Controller;

use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\PageViewFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\CommerceAttributeEntity\Route;
use Symfony\Component\Security\Http\CommerceAttributeEntity\IsGranted;

#[IsGranted('ROLE_SUPPORT')]
#[Route('/operator', name: 'accessing_operator_')]
final class AccessingOperatorController extends AbstractController
{
    #[Route('/accounts', name: 'accounts', methods: ['GET'])]
    public function accounts(
        AccountRepositoryInterface $accountRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response {
        return $pageResponder->respond($pageViewFactory->operatorAccounts(
            $accountRepository->findRecentAccounts(100),
        ));
    }

    #[Route('/accounts/{id}', name: 'account_detail', methods: ['GET'])]
    public function accountDetail(
        int $id,
        AccountRepositoryInterface $accountRepository,
        SecurityEventRepositoryInterface $securityEventRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response {
        $account = $accountRepository->findById($id);

        if (null === $account) {
            throw $this->createNotFoundException();
        }

        return $pageResponder->respond($pageViewFactory->operatorAccountDetail(
            $account,
            $securityEventRepository->findRecentEventsForAccount($account),
        ));
    }

    #[Route('/security-events', name: 'security_events', methods: ['GET'])]
    public function securityEvents(
        SecurityEventRepositoryInterface $securityEventRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response {
        return $pageResponder->respond($pageViewFactory->operatorSecurityEvents(
            $securityEventRepository->findRecentEvents(150),
        ));
    }
}
