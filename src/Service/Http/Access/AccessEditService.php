<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Exception\AccessCrudSkeletonException;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessEditService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->editById($item) : $this->editBySlug((string) $item);
    }

    public function editById(int $id): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.edit_id', (string) $id);
    }

    public function editBySlug(string $slug): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.edit_slug', $slug);
    }
}
