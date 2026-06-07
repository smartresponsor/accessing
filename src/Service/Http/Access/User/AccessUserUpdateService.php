<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access\User;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessUserUpdateService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->updateById($item) : $this->updateBySlug((string) $item);
    }

    public function updateById(int $id): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.update_id', (string) $id);
    }

    public function updateBySlug(string $slug): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.update_slug', $slug);
    }
}
