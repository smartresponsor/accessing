<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Exception\AccessCrudSkeletonException;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessArchiveService
{
    public function __invoke(int|string|null $item = null): Response
    {
        return is_int($item) ? $this->archiveById($item) : $this->archiveBySlug((string) $item);
    }

    public function archiveById(int $id): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.archive_id', (string) $id);
    }

    public function archiveBySlug(string $slug): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.archive_slug', $slug);
    }
}
