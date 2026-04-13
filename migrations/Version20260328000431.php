<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328000431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email_address VARCHAR(180) NOT NULL, display_name VARCHAR(120) NOT NULL, phone_number VARCHAR(32) DEFAULT NULL, roles CLOB NOT NULL, email_verified BOOLEAN NOT NULL, phone_verified BOOLEAN NOT NULL, failed_sign_in_count INTEGER NOT NULL, locked_until DATETIME DEFAULT NULL, last_sign_in_at DATETIME DEFAULT NULL, registered_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_account_email_address ON account (email_address)');
        $this->addSql('CREATE TABLE account_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_identifier VARCHAR(128) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(1000) DEFAULT NULL, trusted BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, invalidated_at DATETIME DEFAULT NULL, account_id INTEGER NOT NULL, CONSTRAINT FK_196FC19C9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_196FC19C9B6B5FBA ON account_session (account_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_account_session_identifier ON account_session (session_identifier)');
        $this->addSql('CREATE TABLE credential (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, password_hash VARCHAR(255) NOT NULL, password_changed_at DATETIME NOT NULL, account_id INTEGER NOT NULL, CONSTRAINT FK_57F1D4B9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57F1D4B9B6B5FBA ON credential (account_id)');
        $this->addSql('CREATE TABLE recovery_code (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code_hash VARCHAR(64) NOT NULL, display_label VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, account_id INTEGER NOT NULL, CONSTRAINT FK_2C8D05849B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2C8D05849B6B5FBA ON recovery_code (account_id)');
        $this->addSql('CREATE TABLE second_factor (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, secret VARCHAR(128) NOT NULL, label VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, last_used_at DATETIME DEFAULT NULL, account_id INTEGER NOT NULL, CONSTRAINT FK_1806C29E9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1806C29E9B6B5FBA ON second_factor (account_id)');
        $this->addSql('CREATE TABLE security_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_type VARCHAR(64) NOT NULL, severity VARCHAR(16) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(1000) DEFAULT NULL, context CLOB NOT NULL, occurred_at DATETIME NOT NULL, account_id INTEGER DEFAULT NULL, CONSTRAINT FK_D712E90D9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D712E90D9B6B5FBA ON security_event (account_id)');
        $this->addSql('CREATE INDEX idx_security_event_occurred_at ON security_event (occurred_at)');
        $this->addSql('CREATE TABLE verification_challenge (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, challenge_type VARCHAR(32) NOT NULL, destination VARCHAR(180) NOT NULL, code_hash VARCHAR(64) NOT NULL, requested_by_ip_address VARCHAR(45) DEFAULT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, consumed_at DATETIME DEFAULT NULL, attempt_count INTEGER NOT NULL, metadata CLOB NOT NULL, account_id INTEGER NOT NULL, CONSTRAINT FK_244137919B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_244137919B6B5FBA ON verification_challenge (account_id)');
        $this->addSql('CREATE INDEX idx_verification_challenge_type_expires ON verification_challenge (challenge_type, expires_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE account_session');
        $this->addSql('DROP TABLE credential');
        $this->addSql('DROP TABLE recovery_code');
        $this->addSql('DROP TABLE second_factor');
        $this->addSql('DROP TABLE security_event');
        $this->addSql('DROP TABLE verification_challenge');
    }
}
