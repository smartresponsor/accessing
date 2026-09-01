<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Exception\AccessCrudSkeletonException;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessDeleteService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->deleteById($item) : $this->deleteBySlug((string) $item);
    }

    public function deleteById(int $id): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.delete_id', (string) $id);
    }

    public function deleteBySlug(string $slug): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.delete_slug', $slug);
    }
}
