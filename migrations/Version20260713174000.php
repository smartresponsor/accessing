<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713174000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill canonical Accessing credential state and remove duplicated access columns.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->abortIf(
            !$platform instanceof PostgreSQLPlatform && !$platform instanceof AbstractMySQLPlatform,
            'This migration supports PostgreSQL and MySQL only.',
        );

        $this->addSql(<<<'SQL'
            INSERT INTO access_credential (user_id, password_hash, password_changed_at)
            SELECT a.id, a.password_hash, COALESCE(a.updated_at, a.created_at, CURRENT_TIMESTAMP)
            FROM access a
            WHERE a.password_hash <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM access_credential credential WHERE credential.user_id = a.id
              )
            SQL);

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(<<<'SQL'
                INSERT INTO access_second_factor (user_id, secret, label, created_at, confirmed_at, revoked_at, last_used_at)
                SELECT a.id, a.totp_secret, a.email, COALESCE(a.created_at, CURRENT_TIMESTAMP),
                       CASE WHEN a.second_factor_enabled THEN COALESCE(a.updated_at, CURRENT_TIMESTAMP) ELSE NULL END,
                       NULL, NULL
                FROM access a
                WHERE a.totp_secret IS NOT NULL AND a.totp_secret <> ''
                  AND NOT EXISTS (
                      SELECT 1 FROM access_second_factor factor WHERE factor.user_id = a.id
                  )
                SQL);
        } else {
            $this->addSql(<<<'SQL'
                INSERT INTO access_second_factor (user_id, secret, label, created_at, confirmed_at, revoked_at, last_used_at)
                SELECT a.id, a.totp_secret, a.email, COALESCE(a.created_at, CURRENT_TIMESTAMP),
                       CASE WHEN a.second_factor_enabled = 1 THEN COALESCE(a.updated_at, CURRENT_TIMESTAMP) ELSE NULL END,
                       NULL, NULL
                FROM access a
                WHERE a.totp_secret IS NOT NULL AND a.totp_secret <> ''
                  AND NOT EXISTS (
                      SELECT 1 FROM access_second_factor factor WHERE factor.user_id = a.id
                  )
                SQL);
        }

        $this->addSql('ALTER TABLE access DROP COLUMN password_hash');
        $this->addSql('ALTER TABLE access DROP COLUMN totp_secret');
        $this->addSql('ALTER TABLE access DROP COLUMN second_factor_enabled');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->abortIf(
            !$platform instanceof PostgreSQLPlatform && !$platform instanceof AbstractMySQLPlatform,
            'This migration supports PostgreSQL and MySQL only.',
        );

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql("ALTER TABLE access ADD password_hash VARCHAR(255) DEFAULT '' NOT NULL");
            $this->addSql('ALTER TABLE access ADD totp_secret VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE access ADD second_factor_enabled BOOLEAN DEFAULT FALSE NOT NULL');
            $this->addSql('UPDATE access a SET password_hash = credential.password_hash FROM access_credential credential WHERE credential.user_id = a.id');
            $this->addSql('UPDATE access a SET totp_secret = factor.secret, second_factor_enabled = (factor.confirmed_at IS NOT NULL AND factor.revoked_at IS NULL) FROM access_second_factor factor WHERE factor.user_id = a.id');

            return;
        }

        $this->addSql("ALTER TABLE access ADD password_hash VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE access ADD totp_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE access ADD second_factor_enabled TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE access a INNER JOIN access_credential credential ON credential.user_id = a.id SET a.password_hash = credential.password_hash');
        $this->addSql('UPDATE access a INNER JOIN access_second_factor factor ON factor.user_id = a.id SET a.totp_secret = factor.secret, a.second_factor_enabled = (factor.confirmed_at IS NOT NULL AND factor.revoked_at IS NULL)');
    }
}
