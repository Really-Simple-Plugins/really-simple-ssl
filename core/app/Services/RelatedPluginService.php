<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

final class RelatedPluginService
{
    /**
     * Should be a Storage object based on one entry in the related config
     */
    protected Storage $pluginConfig;

    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function setPluginConfigBySlug(string $slug): void
    {
        $plugins = $this->app->config->get('related.plugins', []);
        $plugins = array_filter($plugins, static function($plugin) use ($slug){
            return isset($plugin['slug']) && ($plugin['slug'] === $slug);
        });

        $plugin = reset($plugins);
        $this->setPluginConfig($plugin);
    }

    /**
     * Use this method as the default way to set the plugin config.
     */
    public function setPluginConfig(array $pluginConfig): void
    {
        $this->pluginConfig = new Storage($pluginConfig);
    }

    /**
     * Get the list of recommended plugins for the onboarding process.
     *
     * This function prepares plugin data for display in the onboarding wizard.
     * It handles plugin status, actions, and checkbox initialization based on
     * configuration.
     *
     * @return array List of plugin items with their status, actions and UI properties
     *
     * @todo: Plugins that are already installed and activated are still listed
     * in the onboarding.
     */
    public function getOnboardingConfig(): array
    {
        $checkboxes = [];
        $relatedPlugins = $this->app->config->get('related.plugins', []);

        foreach ($relatedPlugins as $config) {
            if (!isset($config['slug'], $config['title'])) {
                continue;
            }

            $this->setPluginConfig($config);
            $activated = $this->pluginConfig->getBoolean('pre_checked');

            $checkboxes[] = [
                'id' => $config['slug'],
                'title' => $config['title'],
                'action' => ($activated ? $this->getAvailablePluginAction() : 'none'),
                'activated' => $activated,
                'current_action' => 'none',
                'default_action' => ($activated ? null : $this->getAvailablePluginAction()),
            ];
        }

        return $checkboxes;
    }

    /**
     * Method returns the url fitting for the context. If a plugin is
     * upgradable, the upgrade_url is returned, otherwise the url entry.
     */
    public function getPluginUrl(): string
    {
        if ($this->pluginCanBeUpgraded()) {
            return $this->pluginConfig->getUrl('upgrade_url');
        }

        return $this->pluginConfig->getUrl('url');
    }

    /**
     * Method returns the action fitting for the context of the plugin.
     */
    public function getAvailablePluginAction(): string
    {
        if ($this->premiumPluginIsInstalled()) {
            return 'installed';
        }

        if ($this->pluginIsDownloadable()) {
            return 'download';
        }

        if ($this->pluginCanBeActivated()) {
            return 'activate';
        }

        if ($this->pluginCanBeUpgraded()) {
            return 'upgrade-to-premium';
        }

        return 'installed';
    }

    /**
     * Execute action for a related plugin
     */
    public function executeAction(string $action): bool
    {
        ob_start();

        switch ($action) {
            case 'download':
                $success = $this->downloadCurrentPlugin();
                break;
            case 'activate':
                $success = $this->activateCurrentPlugin();
                break;
            default:
                $success = false;
        }

        ob_get_clean();

        return $success;
    }

    /**
     * Download the related plugin currently stored in the plugin config
     * property.
     */
    protected function downloadCurrentPlugin(): bool
    {
        $transientName = 'rsp_plugin_download_active';
        if (get_transient($transientName) === $this->pluginConfig->getString('slug')) {
            return true;
        }

        set_transient($transientName, $this->pluginConfig->getString('slug'), MINUTE_IN_SECONDS);

        try {
            $pluginInfo = $this->getCurrentPluginInfo();
        } catch (\Exception $e) {
            return false;
        }

        $downloadLink = esc_url_raw($pluginInfo->versions['trunk']);

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result = $upgrader->install($downloadLink);

        if (is_wp_error($result)) {
            return false;
        }

        delete_transient($transientName);
        return true;
    }

    /**
     * Activate the related plugin currently stored in the plugin config
     * property.
     */
    protected function activateCurrentPlugin(): bool
    {
        $slug = $this->pluginConfig->getString('activation_slug');

        //when activated from the network admin, we assume the user wants network activated
        $networkwide = is_multisite() && is_network_admin();
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }

        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $result = activate_plugin($slug, '', $networkwide);
        if (is_wp_error($result)) {
            return false;
        }

        return true;

    }

    /**
     * Helper method to check if the current plugin is a premium plugin and if
     * it is active.
     */
    protected function premiumPluginIsInstalled(): bool
    {
        return $this->pluginConfig->has('constant_premium') && defined($this->pluginConfig->getString('constant_premium'));
    }

    /**
     * Helper method to check if the current plugin is downloadable.
     */
    protected function pluginIsDownloadable(): bool
    {
        return $this->pluginFileExists() === false;
    }

    /**
     * Helper method to check if the current plugin can be activated.
     */
    protected function pluginCanBeActivated(): bool
    {
        return $this->pluginFileExists() && ($this->pluginIsActive() === false);
    }

    /**
     * Helper method to check if the current plugin can be upgraded. This means
     * the premium version is downloaded, but not yet activated.
     */
    protected function pluginCanBeUpgraded(): bool
    {
        return $this->pluginConfig->has('constant_premium') && !defined($this->pluginConfig->getString('constant_premium'));
    }

    /**
     * Helper method to check if the current plugin file exists.
     */
    protected function pluginFileExists(): bool
    {
        return file_exists(trailingslashit(WP_PLUGIN_DIR).$this->pluginConfig->getString('activation_slug'));
    }

    /**
     * Helper method to check if the current plugin is active.
     */
    public function pluginIsActive(): bool
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($this->pluginConfig->getString('activation_slug'));
    }

    /**
     * Method returns the plugin info for the current plugin. Because we pass
     * the action 'plugin_information' to the plugins_api function, an object is
     * returned if the plugin is found, otherwise a WP_Error.
     * @throws \Exception If the plugin info could not be retrieved
     */
    protected function getCurrentPluginInfo(): object
    {
        $transientName = 'rsp_' . $this->pluginConfig->getString('slug') . '_plugin_info';
        $pluginInfo = get_transient($transientName);

        if (!empty($pluginInfo)) {
            return $pluginInfo;
        }

        if (function_exists('plugins_api') === false) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        $pluginInfo = plugins_api('plugin_information', [
            'slug' => $this->pluginConfig->getString('slug'),
        ]);

        if (is_wp_error($pluginInfo)) {
            throw new \Exception('Unable to get plugin info');
        }

        set_transient($transientName, $pluginInfo, WEEK_IN_SECONDS);
        return $pluginInfo;
    }

}