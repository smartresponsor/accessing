<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330183000 extends AbstractMigration
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getDescription(): string
    {
        return 'Add TOTP secret column to the Accessing user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_user ADD totp_secret VARCHAR(255) DEFAULT NULL');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_user DROP totp_secret');
    }
}
