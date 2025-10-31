<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Bootstrap;

use ReallySimplePlugins\RSS\Core\Managers\FeatureManager;
use ReallySimplePlugins\RSS\Core\Managers\ProviderManager;
use ReallySimplePlugins\RSS\Core\Managers\EndpointManager;
use ReallySimplePlugins\RSS\Core\Managers\ControllerManager;

class Plugin
{
    private App $app;
    private FeatureManager $featureManager;
    private ProviderManager $providerManager;
    private EndpointManager $endpointManager;
    private ControllerManager $controllerManager;

    /**
     * Plugin constructor
     */
    public function __construct()
    {
        $this->app = App::getInstance();

        // todo - should these be added to the container too? Rather not tho
        $this->featureManager = new FeatureManager($this->app);
        $this->providerManager = new ProviderManager($this->app);
        $this->controllerManager = new ControllerManager($this->app);
        $this->endpointManager = new EndpointManager($this->app);
    }

    /**
     * Boot the plugin
     */
    public function boot(): void
    {
        $this->registerEnvironment();

	    $pluginBaseFile = (defined('rsssl_file') && !empty(rsssl_file) ? rsssl_file : '');
	    if (empty($pluginBaseFile)) {
		    $pluginBaseFile = (dirname(__DIR__, 2). DIRECTORY_SEPARATOR . plugin_basename(dirname(__DIR__, 2)) . '.php');
	    }
		register_activation_hook($pluginBaseFile, [$this, 'activation']);
        register_deactivation_hook($pluginBaseFile, [$this, 'deactivation']);
        register_uninstall_hook($pluginBaseFile, 'ReallySimplePlugins\RSS\Core\Bootstrap\Plugin::uninstall');

        // Priority BEFORE main plugin to be able to hook into actions/filters
        add_action('plugins_loaded', [$this, 'registerProviders'], 5);
        add_action('plugins_loaded', [$this, 'loadPluginTextDomain'], 10); // Config must be provided by registerProviders
        add_action('rss_core_providers_loaded', [$this->featureManager, 'registerFeatures']); // Makes sure features exist when Controllers need them
        add_action('rss_core_features_loaded', [$this, 'registerControllers']); // Control the functionality of the plugin
        add_action('rss_core_controllers_loaded', [$this, 'checkForUpgrades']); // Makes sure Controllers can hook into the upgrade process
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_action('admin_init', [$this, 'fireActivationHook']);
    }

    /**
     * Register the plugin environment. The value of the environment will
     * determine which domain and app_key are used for the API calls. The
     * default value is production and can be [production|development].
     * See {@see config/environment.php} for the actual values.
     */
    public function registerEnvironment(): void
    {
        if (!defined('RSS_CORE_ENV')) {
            define('RSS_CORE_ENV', 'development');
        }
    }

    /**
     * Load the plugin text domain for translations
     */
    public function loadPluginTextDomain(): void
    {
        load_plugin_textdomain('really-simple-ssl', false, $this->app->config->getString('env.plugin.lang_path'));
    }

    /**
     * Method that fires on activation. It creates a flag in the database
     * options table to indicate that the plugin is being activated. Flag is
     * used by {@see fireActivationHook} to run the activation hook only once.
     */
    public function activation(): void
    {
        global $pagenow;

        // Set the flag on activation
        update_option('rss_core_activation_flag', true, false);
        update_option('rss_core_activation_source_page', sanitize_text_field($pagenow), false);

        // Flush rewrite rules to ensure the new routes are available
        // add_action('shutdown', 'flush_rewrite_rules'); - todo: not yet handled by core
    }

    /**
     * Method fires the activation hook. But only if the plugin is being
     * activated. The flag is set in the database options table
     * {@see activation} and is used to determine if the plugin is being
     * activated. This method removes the flag after it has been used.
     */
    public function fireActivationHook(): void
    {
        if (get_option('rss_core_activation_flag', false) === false) {
            return;
        }

        // Get the source page where the activation was triggered from
        $source = get_option('rss_core_activation_source_page', 'unknown');

        // Remove the activation flag so the action doesn't run again. Do it
        // before the action so its deleted before anything can go wrong.
        delete_option('rss_core_activation_flag');
        delete_option('rss_core_activation_source_page');

        // Gives possibility to hook into the activation process
        do_action('rss_core_activation', $source); // !important
    }

    /**
     * Method that fires on deactivation
     */
    public function deactivation(): void
    {
        // Silence is golden
    }

    /**
     * Method that fires on uninstall
     */
    public static function uninstall(): void
    {
        // todo - uninstall not yet handled by core.
    }

    /**
     * Register Plugin providers. Providers will add functionality to the
     * container. These functionalities are lazy loaded for initialization
     * until its first use. Therefor it is not hooked into an action.
     * @uses do_action rss_core_providers_loaded
     */
    public function registerProviders(): void
    {
        $this->providerManager->register([
            \ReallySimplePlugins\RSS\Core\Providers\ConfigServiceProvider::class,
            \ReallySimplePlugins\RSS\Core\Providers\RequestServiceProvider::class,
        ]);
    }

    /**
     * Register Controllers. Hooked into rss_core_features_loaded to make sure
     * features are available to the Controllers.
     * @uses do_action rss_core_controllers_loaded
     */
    public function registerControllers(): void
    {
        $this->controllerManager->register([
            \ReallySimplePlugins\RSS\Core\Controllers\DashboardController::class,
        ]);
    }

    /**
     * Register the plugins REST API endpoint instances. Hooked into
     * rest_api_init to make sure the REST API is available.
     * @uses do_action rss_core_endpoints_loaded
     */
    public function registerEndpoints(): void
    {
        $this->endpointManager->register([
        ]);
    }

    /**
     * Fire an action when the plugin is upgraded from one version to another.
     * Hooked into rss_core_controllers_loaded to make sure Controllers can
     * hook into rss_core_plugin_version_upgrade.
     *
     * @internal Note the starting underscore in the option name. This is to
     * prevent the option from being deleted when a user logs out. As if
     * it is a private Really Simple Security Core option.
     *
     * @uses do_action rss_core_plugin_version_upgrade
     */
    public function checkForUpgrades(): void
    {
        // todo - upgrades not yet handled by core.
    }

}