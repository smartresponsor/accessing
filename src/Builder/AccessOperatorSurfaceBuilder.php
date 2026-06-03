<?php

declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessOperatorSurfaceBuilder
{
    public function accounts(
        AccessAccountRepositoryInterface $accountRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->operatorAccounts(
            $accountRepository->findRecentAccounts(100),
        ));
    }

    public function accountDetail(
        int $id,
        AccessAccountRepositoryInterface $accountRepository,
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
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
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->operatorSecurityEvents(
            $securityEventRepository->findRecentEvents(150),
        ));
    }
}
