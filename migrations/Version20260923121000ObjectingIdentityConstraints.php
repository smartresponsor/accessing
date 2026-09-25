<?php

declare(strict_types=1);

namespace App\Accessing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923121000ObjectingIdentityConstraints extends AbstractMigration
{
    /** @var array<string, array{uuid: string, slug: string}> */
    private const array IDENTITY_INDEXES = [
        'access' => [
            'uuid' => 'uniq_6692b544c6a6cb5',
            'slug' => 'uniq_6692b54588a771',
        ],
        'access_external_identity' => [
            'uuid' => 'uniq_8b5367e24c6a6cb5',
            'slug' => 'uniq_8b5367e2588a771',
        ],
    ];

    public function getDescription(): string
    {
        return 'Reconcile Accessing Objecting identity columns and replace Doctrine hash unique indexes with Objecting-owned semantic names.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Accessing Objecting identity reconciliation supports PostgreSQL only.',
        );

        foreach (self::IDENTITY_INDEXES as $tableName => $legacyIndexes) {
            $this->abortIf(!$schema->hasTable($tableName), sprintf('Required Accessing table %s is missing.', $tableName));
            $table = $schema->getTable($tableName);

            $this->renameLegacyColumn($table, $tableName, 'object_uuid', 'uuid');
            $this->renameLegacyColumn($table, $tableName, 'object_slug', 'slug');

            $this->renameIndex(
                $legacyIndexes['uuid'],
                'uniq_'.$tableName.'_uuid',
            );
            $this->renameIndex(
                $legacyIndexes['slug'],
                'uniq_'.$tableName.'_slug',
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Objecting identity column and constraint canonicalization is intentionally irreversible.',
        );
    }

    private function renameLegacyColumn(Table $table, string $tableName, string $legacy, string $canonical): void
    {
        $legacyExists = $table->hasColumn($legacy);
        $canonicalExists = $table->hasColumn($canonical);

        $this->abortIf(
            $legacyExists && $canonicalExists,
            sprintf('%s contains both %s and %s; manual reconciliation is required.', $tableName, $legacy, $canonical),
        );

        if ($legacyExists) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s', $tableName, $legacy, $canonical));
        }
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
        RAISE EXCEPTION 'Both legacy index %1$s and canonical index %2$s exist; manual reconciliation is required.';
    END IF;
END
$$
SQL,
            $legacy,
            $canonical,
        ));
    }
}

