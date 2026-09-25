<?php

declare(strict_types=1);

namespace App\Accessing\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923093000CanonicalAccessingIndexNames extends AbstractMigration
{
    /** @var array<string, string> */
    private const array INDEX_RENAMES = [
        'uniq_2f4185caa76ed395' => 'uniq_access_credential_user',
        'idx_8ffa9ca4a76ed395' => 'idx_access_mobile_pending_auth_user',
        'uniq_8ffa9ca4b3bc57da' => 'uniq_access_mobile_pending_auth_token_hash',
        'idx_c75f29ca76ed395' => 'idx_access_mobile_session_user',
        'uniq_c75f29c613fecdf' => 'uniq_access_mobile_session_session_id',
        'uniq_c75f29c7288f9ec' => 'uniq_access_mobile_session_refresh_token_hash',
        'uniq_c75f29c9982cf5b' => 'uniq_access_mobile_session_access_token_hash',
        'idx_102c6befa76ed395' => 'idx_access_passkey_challenge_user',
        'idx_2961a622a76ed395' => 'idx_access_passkey_credential_user',
        'idx_b48369a0a76ed395' => 'idx_access_recovery_code_user',
        'idx_fe9dfda2a76ed395' => 'idx_access_reset_password_request_user',
        'uniq_8008aebaa76ed395' => 'uniq_access_second_factor_user',
        'idx_eb8903b0a76ed395' => 'idx_access_security_event_user',
        'idx_18610fc1a76ed395' => 'idx_access_session_user',
        'uniq_18610fc197b88831' => 'uniq_access_session_identifier',
        'idx_dd12beb9a76ed395' => 'idx_access_verification_challenge_user',
    ];

    public function getDescription(): string
    {
        return 'Replace Doctrine hash-derived Accessing index names with stable semantic names.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Accessing canonical index-name migration supports PostgreSQL only.',
        );

        foreach (self::INDEX_RENAMES as $legacy => $canonical) {
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

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Canonical Accessing schema-object naming convergence is intentionally irreversible.',
        );
    }
}

