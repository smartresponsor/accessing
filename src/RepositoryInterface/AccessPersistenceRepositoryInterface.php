<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 * Owns low-level Doctrine persistence primitives that must not leak into application services or commands.
 */
interface AccessPersistenceRepositoryInterface
{
    /**
     * Registers an entity for persistence without forcing a transaction boundary.
     */
    public function persist(object $entity): void;

    /**
     * Registers an entity for removal without forcing a transaction boundary.
     */
    public function remove(object $entity): void;

    /**
     * Flushes the current Doctrine unit of work.
     */
    public function flush(): void;

    /**
     * Drops and recreates the schema from the current Doctrine metadata.
     */
    public function resetSchema(): void;

    /**
     * Executes the supplied fixture set through the current ORM manager.
     *
     * @param array<array-key, FixtureInterface> $fixtures
     */
    public function executeFixtures(array $fixtures, bool $append = false): void;
}
