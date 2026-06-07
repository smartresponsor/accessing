<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429190000 extends AbstractMigration
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getDescription(): string
    {
        return 'Add last sign-in timestamp to access.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access ADD COLUMN last_sign_in_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access DROP COLUMN last_sign_in_at');
    }
}
