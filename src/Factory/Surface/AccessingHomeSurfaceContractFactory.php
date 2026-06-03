<?php

declare(strict_types=1);

namespace App\Accessing\Factory\Surface;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Value\Surface\AccessingHomeSurfaceContract;

final readonly class AccessingHomeSurfaceContractFactory
{
    public function __construct(
        private string $accessingProductName,
    ) {
    }

    /**
     * @param array<int, mixed> $events
     */
    public function create(AccessAccountEntity $account, array $events): AccessingHomeSurfaceContract
    {
        return new AccessingHomeSurfaceContract(
            word: AccessingHomeSurfaceContract::WORD,
            view: 'account.overview',
            templateName: 'access/index.html.twig',
            slotMap: [
                'main.body' => 'main',
                'top.panel' => 'brand',
                'right.panel' => 'actions',
            ],
            slots: [
                'accessingProductName' => $this->accessingProductName,
                'account' => $account,
                'events' => $events,
            ],
        );
    }
}
