<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access\User;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessUserDuplicateService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->duplicateById($item) : $this->duplicateBySlug((string) $item);
    }

    public function duplicateById(int $id): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.duplicate_id', (string) $id);
    }

    public function duplicateBySlug(string $slug): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.duplicate_slug', $slug);
    }
}
