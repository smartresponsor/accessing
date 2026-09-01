<?php

declare(strict_types=1);

namespace App\Accessing\Factory\Surface;

use App\Accessing\Contract\Surface\AccessHomeSurfaceContract;
use App\Accessing\Entity\AccessEntity;

final readonly class AccessHomeSurfaceContractFactory
{
    public function __construct(
        private string $accessingProductName,
    ) {
    }

    /**
     * @param array<int, mixed> $events
     */
    public function create(AccessEntity $user, array $events): AccessHomeSurfaceContract
    {
        return new AccessHomeSurfaceContract(
            word: AccessHomeSurfaceContract::WORD,
            view: 'access.overview',
            templateName: 'access/index.html.twig',
            slotMap: [
                'main.body' => 'main',
                'top.panel' => 'brand',
                'right.panel' => 'actions',
            ],
            slots: [
                'accessingProductName' => $this->accessingProductName,
                'user' => $user,
                'events' => $events,
            ],
        );
    }
}
