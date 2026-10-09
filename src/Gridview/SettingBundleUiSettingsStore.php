<?php

namespace App\Gridview;

use Fedale\GridviewBundle\UiSettings\UiSettingsStoreInterface;
use Fedale\SettingBundle\Contract\SettingsManagerInterface;

/**
 * Stores the gridview UI settings in the database through fedale/setting-bundle:
 * one JSON setting per scope (`gridview.ui._global`, `gridview.ui.<gridId>`).
 *
 * A whole bag per scope means "inherit" is just a missing key, so nothing ever
 * has to be deleted (the settings manager has no delete). Going through the
 * manager, never the repository, keeps its cache in sync on every save.
 */
final class SettingBundleUiSettingsStore implements UiSettingsStoreInterface
{
    private const PREFIX = 'gridview.ui.';

    public function __construct(private readonly SettingsManagerInterface $settings)
    {
    }

    public function load(string $scope): array
    {
        $values = $this->settings->get(self::PREFIX . $scope, []);

        return \is_array($values) ? $values : [];
    }

    public function save(string $scope, array $values): void
    {
        $this->settings->set(self::PREFIX . $scope, $values, null, 'json');
    }
}
