<?php

declare(strict_types=1);

namespace App\Accessing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003193000 extends AbstractMigration
{
    /** @var array<string, string> */
    private const array INDEX_RENAMES = [
        'uniq_6692b54d17f50a6' => 'uniq_access_uuid',
        'uniq_6692b54989d9b62' => 'uniq_access_slug',
        'uniq_8b5367e2d17f50a6' => 'uniq_access_external_identity_uuid',
        'uniq_8b5367e2989d9b62' => 'uniq_access_external_identity_slug',
    ];

    public function getDescription(): string
    {
        return 'Reconcile baseline Objecting identity index hashes with canonical deterministic names.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Accessing Objecting identity index reconciliation supports PostgreSQL only.',
        );

        foreach (self::INDEX_RENAMES as $legacy => $canonical) {
            $this->renameIndex($legacy, $canonical);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Canonical Objecting identity index naming convergence is intentionally irreversible.',
        );
    }

    private function renameIndex(string $legacy, string $canonical): void
    {
        $this->addSql(sprintf(
            <<<'SQL'
DO $$
BEGIN
    IF to_regclass('public.%1$s') IS NOT NULL AND to_regclass('public.%2$s') IS NULL THEN
        EXECUTE 'ALTER INDEX %1$s RENAME TO %2$s';
    ELSIF to_regclass('public.%1$s') IS NOT NULL AND to_regclass('public.%2$s') IS NOT NULL THEN
        RAISE EXCEPTION 'Both baseline index %1$s and canonical index %2$s exist; manual reconciliation is required.';
    END IF;
END
$$
SQL,
            $legacy,
            $canonical,
        ));
    }
}
