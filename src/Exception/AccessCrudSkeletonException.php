<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AccessCrudSkeletonException extends BadRequestHttpException
{
    public static function unsupported(string $routeKey, string $subject = ''): self
    {
        $suffix = '' !== $subject ? sprintf(' for "%s"', $subject) : '';

        return new self(sprintf('CRUD route "%s" is declared by grammar but has no business entrypoint yet%s.', $routeKey, $suffix));
    }
}
