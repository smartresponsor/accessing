<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801182500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Objecting-backed external OAuth identities for Accessing.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->abortIf(
            !$platform instanceof PostgreSQLPlatform && !$platform instanceof AbstractMySQLPlatform,
            'This migration supports PostgreSQL and MySQL only.',
        );

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('CREATE TABLE access_external_identity (id SERIAL NOT NULL, user_id INT NOT NULL, object_uuid BYTEA NOT NULL, object_slug VARCHAR(190) NOT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_source VARCHAR(190) DEFAULT NULL, object_provider VARCHAR(190) DEFAULT NULL, object_external_id VARCHAR(190) DEFAULT NULL, object_source_type VARCHAR(120) DEFAULT NULL, email VARCHAR(180) NOT NULL, email_verified BOOLEAN NOT NULL, display_name VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(2048) DEFAULT NULL, last_authenticated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX uniq_access_external_identity_provider_subject ON access_external_identity (object_provider, object_external_id)');
            $this->addSql('CREATE UNIQUE INDEX uniq_access_external_identity_object_uuid ON access_external_identity (object_uuid)');
            $this->addSql('CREATE UNIQUE INDEX uniq_access_external_identity_object_slug ON access_external_identity (object_slug)');
            $this->addSql('CREATE INDEX idx_access_external_identity_user ON access_external_identity (user_id)');
            $this->addSql('ALTER TABLE access_external_identity ADD CONSTRAINT fk_access_external_identity_user FOREIGN KEY (user_id) REFERENCES access (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

            return;
        }

        $this->addSql('CREATE TABLE access_external_identity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, object_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:binary)\', object_slug VARCHAR(190) NOT NULL, object_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', object_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_source VARCHAR(190) DEFAULT NULL, object_provider VARCHAR(190) DEFAULT NULL, object_external_id VARCHAR(190) DEFAULT NULL, object_source_type VARCHAR(120) DEFAULT NULL, email VARCHAR(180) NOT NULL, email_verified TINYINT(1) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(2048) DEFAULT NULL, last_authenticated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_access_external_identity_user (user_id), UNIQUE INDEX uniq_access_external_identity_provider_subject (object_provider, object_external_id), UNIQUE INDEX uniq_access_external_identity_object_uuid (object_uuid), UNIQUE INDEX uniq_access_external_identity_object_slug (object_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE access_external_identity ADD CONSTRAINT fk_access_external_identity_user FOREIGN KEY (user_id) REFERENCES access (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE access_external_identity');
    }
}
