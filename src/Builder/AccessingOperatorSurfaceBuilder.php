<?php

declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\PageViewFactoryInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessingOperatorSurfaceBuilder
{
    public function accounts(
        AccountRepositoryInterface $accountRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->operatorAccounts(
            $accountRepository->findRecentAccounts(100),
        ));
    }

    public function accountDetail(
        int $id,
        AccountRepositoryInterface $accountRepository,
        SecurityEventRepositoryInterface $securityEventRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $accountRepository->findById($id);

        if (null === $account) {
            throw new NotFoundHttpException();
        }

        return $pageResponder->respond($pageViewFactory->operatorAccountDetail(
            $account,
            $securityEventRepository->findRecentEventsForAccount($account),
        ));
    }

    public function securityEvents(
        SecurityEventRepositoryInterface $securityEventRepository,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->operatorSecurityEvents(
            $securityEventRepository->findRecentEvents(150),
        ));
    }
}
