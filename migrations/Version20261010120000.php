<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table of fedale/setting-bundle's scoped settings, now backing the gridview UI
 * settings modal. The values saved by the former App\Gridview\SettingBundleUiSettingsStore
 * (one JSON row per scope in the setting table, named gridview.ui.<scope>) are
 * moved over, one row per key.
 */
final class Version20261010120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the setting_scoped table (fedale/setting-bundle) and move the gridview UI settings into it';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting_scoped (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tenant_id INTEGER NOT NULL, namespace VARCHAR(100) NOT NULL, level VARCHAR(50) NOT NULL, scope_id VARCHAR(191) DEFAULT \'\' NOT NULL, setting_key VARCHAR(100) NOT NULL, value CLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_setting_scoped ON setting_scoped (tenant_id, namespace, level, scope_id, setting_key)');

        $rows = $this->connection->fetchAllAssociative(
            "SELECT tenant_id, name, value FROM setting WHERE name LIKE 'gridview.ui.%' AND type = 'json'",
        );

        foreach ($rows as $row) {
            $scope = substr($row['name'], \strlen('gridview.ui.'));
            [$level, $scopeId] = $scope === '_global' ? ['global', ''] : ['grid', $scope];

            foreach ((array) json_decode($row['value'], true) as $key => $value) {
                if (!\is_scalar($value)) {
                    continue;
                }

                $this->addSql(
                    'INSERT INTO setting_scoped (tenant_id, namespace, level, scope_id, setting_key, value, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                    [(int) $row['tenant_id'], 'gridview.ui', $level, $scopeId, (string) $key, \is_bool($value) ? ($value ? '1' : '0') : (string) $value],
                );
            }
        }

        $this->addSql("DELETE FROM setting WHERE name LIKE 'gridview.ui.%' AND type = 'json'");
    }

    public function down(Schema $schema): void
    {
        // The UI settings are not moved back to the setting table.
        $this->addSql('DROP TABLE setting_scoped');
    }
}
