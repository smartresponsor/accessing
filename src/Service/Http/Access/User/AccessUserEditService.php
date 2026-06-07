<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access\User;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessUserEditService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->editById($item) : $this->editBySlug((string) $item);
    }

    public function editById(int $id): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.edit_id', (string) $id);
    }

    public function editBySlug(string $slug): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.edit_slug', $slug);
    }
}
