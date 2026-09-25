<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\ProviderInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the owner resolve service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessOwnerResolveService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessCurrentContextProviderInterface $contextProvider,
        private AccessRepositoryInterface $accessRepository,
    ) {
    }

    /**
     * Executes the require access operation within the canonical Accessing component workflow.
     */
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
