<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;

/**
 * Defines the repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessEntity $user, bool $flush = false): void;

    /**
     * Executes the remove operation within the canonical Accessing component workflow.
     */
    public function remove(AccessEntity $user, bool $flush = false): void;

    /**
     * Executes the find by id operation within the canonical Accessing component workflow.
     */
    public function findById(int $id): ?AccessEntity;

    /**
     * Executes the find one by email address operation within the canonical Accessing component workflow.
     */
    public function findOneByEmailAddress(string $emailAddress): ?AccessEntity;

    /**
     * @return list<AccessEntity>
     */
    public function findRecentUsers(int $limit = 20): array;
}
