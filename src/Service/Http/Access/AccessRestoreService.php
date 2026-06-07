<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessRestoreService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->restoreById($item) : $this->restoreBySlug((string) $item);
    }

    public function restoreById(int $id): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.restore_id', (string) $id);
    }

    public function restoreBySlug(string $slug): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.restore_slug', $slug);
    }
}
