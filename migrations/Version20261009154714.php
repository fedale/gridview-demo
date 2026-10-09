<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table of fedale/setting-bundle, backing the gridview UI settings modal.
 */
final class Version20261009154714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the setting table (fedale/setting-bundle) for the gridview UI settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tenant_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, value CLOB NOT NULL, type VARCHAR(20) DEFAULT \'string\' NOT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX idx_setting_tenant_active ON setting (tenant_id, active)');
        $this->addSql('CREATE UNIQUE INDEX uniq_setting_tenant_name ON setting (tenant_id, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting');
    }
}
