<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessOwnerResolveService
{
    public function __construct(
        private AccessCurrentContextProviderInterface $contextProvider,
        private AccessRepositoryInterface $accessRepository,
    ) {
    }

    public function requireAccess(): AccessEntity
    {
        $context = $this->contextProvider->current();
        if (null === $context || !is_int($context->userId())) {
            throw new NotFoundHttpException();
        }

        $access = $this->accessRepository->findById($context->userId());
        if (null === $access) {
            throw new NotFoundHttpException();
        }

        return $access;
    }
}
