<?php
/**
 * Plugin Name: Really Simple Security
 * Plugin URI: https://really-simple-ssl.com
 * Description: Easily improve site security with WordPress Hardening, Two-Factor Authentication (2FA), Login Protection, Vulnerability Detection and SSL certificate generation.
 * Version: 9.8.5
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Really Simple Security
 * Author URI: https://really-simple-ssl.com/about-us
 * License: GPL2
 * Text Domain: really-simple-ssl
 * Domain Path: /languages
 * Network: true
 */
/*  Copyright 2023  Really Simple Plugins BV  (email : support@really-simple-ssl.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("you do not have access to this page!");

if ( ! defined( 'rsssl_file' ) ) {
    define( 'rsssl_file', __FILE__ );
}

if ( ! defined( 'rsssl_plugin' ) ) {
    define('rsssl_plugin', plugin_basename(__FILE__));
}

if (!function_exists('rsssl_activation_check')) {
    function rsssl_activation_check()
    {
        update_option('rsssl_activation', true, false );
        update_option('rsssl_show_onboarding', true, false );
        update_option('rsssl_redirect_to_settings_page', true, HOUR_IN_SECONDS );
    }
    register_activation_hook( __FILE__, 'rsssl_activation_check' );
}

// Load alternate deactivation helpers early without loading functions.php before constants are set.
$rsssl_path = trailingslashit( plugin_dir_path( __FILE__ ) );
require_once $rsssl_path . 'deactivate-alternate.php';

rsssl_deactivate_alternate( 'pro' );

if ( ! class_exists( 'REALLY_SIMPLE_SSL' ) ) {
    class REALLY_SIMPLE_SSL {

        private static $instance;
        public $front_end;
        public $mixed_content_fixer;
        public $multisite;
        public $cache;
        public $server;
        public $admin;
        public $progress;
        public $onboarding;
        public $placeholder;
        public $certificate;
        public $wp_cli;
        public $mailer_admin;
        public $site_health;
        public $vulnerabilities;
        public $settingsConfigService;

        private function __construct()
        {
        }

        public static function instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof REALLY_SIMPLE_SSL)) {
                self::$instance = new REALLY_SIMPLE_SSL;
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->front_end = new rsssl_front_end();
                self::$instance->mixed_content_fixer = new rsssl_mixed_content_fixer();

                if ( is_multisite() ) {
                    self::$instance->multisite = new rsssl_multisite();
                }
                if ( rsssl_admin_logged_in() ) {
                    self::$instance->cache = new rsssl_cache();
                    self::$instance->placeholder = new rsssl_placeholder();
                    self::$instance->server = new rsssl_server();
                    self::$instance->admin = new rsssl_admin();
                    self::$instance->mailer_admin = new rsssl_mailer_admin();
                    self::$instance->progress = new rsssl_progress();
                    self::$instance->certificate = new rsssl_certificate();
                    self::$instance->site_health = new rsssl_site_health();

                    if (class_exists('\ReallySimplePlugins\RSS\Core\Services\SettingsConfigService')) {
                        self::$instance->settingsConfigService = new \ReallySimplePlugins\RSS\Core\Services\SettingsConfigService();
                    }

                    if ( defined( 'WP_CLI' ) && WP_CLI ) {
                        self::$instance->wp_cli = new rsssl_wp_cli();
                    }
                }
                self::$instance->hooks();
            }
            return self::$instance;
        }

        private function setup_constants()
        {
            define('rsssl_url', plugin_dir_url(__FILE__));
            define('rsssl_path', trailingslashit(plugin_dir_path(__FILE__)));
            define('rsssl_template_path', trailingslashit(plugin_dir_path(__FILE__)).'grid/templates/');
            define('rsssl_version', '9.8.5');
            define('rsssl_le_cron_generation_renewal_check', 20);
            define('rsssl_le_manual_generation_renewal_check', 15);
        }
        private function includes()
        {
            require_once(rsssl_path . 'class-front-end.php');
            require_once(rsssl_path . 'functions.php');
            require_once(rsssl_path . 'class-mixed-content-fixer.php');
            if ( defined( 'WP_CLI' ) && WP_CLI ) {
                require_once( rsssl_path . 'class-wp-cli.php');
            }
            if ( is_multisite() ) {
                require_once( rsssl_path . 'class-multisite.php');
            }
            if ( rsssl_admin_logged_in() ) {
                require_once( rsssl_path . 'upgrade.php');
                require_once( rsssl_path . 'settings/settings.php' );
                require_once( rsssl_path . 'modal/modal.php' );
                require_once( rsssl_path . 'placeholders/class-placeholder.php' );
                require_once( rsssl_path . 'class-admin.php');
                require_once( rsssl_path . 'mailer/class-mail-admin.php');
                require_once( rsssl_path . 'class-cache.php');
                require_once( rsssl_path . 'class-server.php');
                require_once( rsssl_path . 'progress/class-progress.php');
                require_once( rsssl_path . 'class-certificate.php');
                require_once( rsssl_path . 'class-site-health.php');
                require_once( rsssl_path . 'mailer/class-mail.php');
                require_once( rsssl_path . 'lets-encrypt/letsencrypt.php' );
                if ( isset($_GET['install_pro'])) {
                    require_once( rsssl_path . 'upgrade/upgrade-to-pro.php');
                }
            }

            require_once( rsssl_path . 'lets-encrypt/cron.php' );
            require_once( rsssl_path . '/security/security.php');
            require_once( rsssl_path . '/rsssl-auto-loader.php' );
        }

        private function hooks()
        {
            /**
             * Fire custom hook
             */
            if ( rsssl_admin_logged_in() ) {
                add_action('admin_notices', array( $this, 'admin_notices'));
                if ( is_multisite() ) {
                    add_action('network_admin_notices', array( $this, 'admin_notices'));
                }
            }

            add_action('wp_loaded', array(self::$instance->front_end, 'force_ssl'), 20);
            if ( rsssl_admin_logged_in() ) {
                add_action('plugins_loaded', array(self::$instance->admin, 'init'), 10);
            }
        }

        /**
         * Notice about possible compatibility issues with add ons
         */
        public static function admin_notices() {
            //prevent showing on edit screen, as gutenberg removes the class which makes it editable.
            $screen = get_current_screen();
            if ( $screen && $screen->base === 'post' ) return;
            if ( self::has_old_addon('really-simple-ssl-pro/really-simple-ssl-pro.php') ||
                self::has_old_addon('really-simple-ssl-pro-multisite/really-simple-ssl-pro-multisite.php' )
            ) {
                ?>
                <div id="message" class="error notice really-simple-plugins">
                    <p><?php echo __("Update Really Simple SSL Pro: the plugin needs to be updated to the latest version to be compatible.","really-simple-ssl");?></p>
                    <p>
                        <?php printf(__("Visit the plugins overview or %srenew your license%s.","really-simple-ssl"),'<a href="https://really-simple-ssl.com/pro/?mtm_campaign=renew&mtm_source=free&mtm_content=upgrade" target="_blank" rel="noopener noreferrer">','</a>'); ?>
                    </p>
                </div>
                <?php
            }
        }

        /**
         * Check if we have a pre 4.0 add on active which should be upgraded
         * @param $file
         *
         * @return bool
         */

        public static function has_old_addon($file) {
            require_once(ABSPATH.'wp-admin/includes/plugin.php');
            $data = false;
            if ( is_plugin_active($file)) $data = get_plugin_data( trailingslashit(WP_PLUGIN_DIR) . $file, false, false );
            if ($data && version_compare($data['Version'], '7.0.6', '<')) {
                return true;
            }

            if ($data && $data['Name']==='Really Simple SSL social' && version_compare($data['Version'], '4.0.8', '<')) {
                return true;
            }
            return false;
        }
    }
}

if ( !defined('RSSSL_DEACTIVATING_ALTERNATE')
    && !function_exists('RSSSL')
) {
    function RSSSL() {
        return REALLY_SIMPLE_SSL::instance();
    }
    add_action('plugins_loaded', 'RSSSL', 8);

    require_once __DIR__ . '/functions.php';

    if (file_exists(__DIR__  . '/core/really-simple-security-core.php')) {
        require_once __DIR__  . '/core/really-simple-security-core.php';
    }
}

if ( ! function_exists('rsssl_add_manage_security_capability')){
    /**
     * Add a user capability to WordPress and add to admin and editor role
     */
    function rsssl_add_manage_security_capability(){
        $role = get_role( 'administrator' );
        if( $role && !$role->has_cap( 'manage_security' ) ){
            $role->add_cap( 'manage_security' );
        }
    }

    register_activation_hook( __FILE__, 'rsssl_add_manage_security_capability' );
}

if ( ! function_exists( 'rsssl_user_can_manage' ) ) {
    /**
     * Check if user has required capability
     *
     * On a network activation, the settings are network-wide. A subsite
     * administrator can keep manage_security from a previous per-site
     * activation, so the user must also be able to manage network options.
     *
     * @return bool
     */
    function rsssl_user_can_manage() {
        #allow wp-cli access to activate ssl
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        if ( ! current_user_can( 'manage_security' ) ) {
            return false;
        }

        $networkwide = function_exists( 'rsssl_is_networkwide_active' ) && rsssl_is_networkwide_active();
        if ( $networkwide ) {
            return current_user_can( 'manage_network_options' );
        }

        return true;
    }
}

if ( !function_exists('rsssl_admin_logged_in')){
    function rsssl_admin_logged_in(){
        $wpcli = defined( 'WP_CLI' ) && WP_CLI;
        return (is_admin() && rsssl_user_can_manage()) || rsssl_is_logged_in_rest() ||  wp_doing_cron() || $wpcli || defined('RSSSL_DOING_SYSTEM_STATUS') || defined('RSSSL_LEARNING_MODE');
    }
}



if ( ! function_exists( 'rsssl_is_logged_in_rest' ) ) {
    function rsssl_is_logged_in_rest(): bool {
        if ( ! rsssl_user_can_manage() ) {
            return false;
        }

        if ( isset( $_GET['rest_route'] ) && ! is_string( $_GET['rest_route'] ) ) {
            return false;
        }

        $namespace = 'really-simple-security/v1/';

        // WordPress gives an explicit rest_route precedence over the URL path.
        if ( isset( $_GET['rest_route'] ) ) {
            $route = wp_unslash( $_GET['rest_route'] );
            $route = ltrim( $route, '/' );
            $route = trailingslashit( $route );

            return strpos( $route, $namespace ) === 0;
        }

        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        $request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
        $path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
        $path = trailingslashit( $path );
        $rest_route = rest_get_url_prefix() . '/' . $namespace;

        // WordPress registers both forms; rest_url() needs WP_Rewrite, which is unavailable at plugins_loaded.
        foreach ( [ $rest_route, 'index.php/' . $rest_route ] as $route ) {
            $rest_url = home_url( '/' . $route );
            $rest_path = (string) wp_parse_url( $rest_url, PHP_URL_PATH );
            if ( strpos( $path, $rest_path ) === 0 ) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists( 'rsssl_maybe_activate_recommended_features_extendify' ) ) {
    function rsssl_maybe_activate_recommended_features_extendify() {
        if ( get_option( 'rsssl_activated_recommended_features_extendify' ) || ! defined( 'EXTENDIFY_PARTNER_ID' ) || defined( 'rsssl_pro' ) ) {
            return;
        }

        try {
            RSSSL()->admin->activate_recommended_features();
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Really Simple Security: recommended features activation failed: ' . $e->getMessage() );
                return;
            }
        }

        update_option( 'rsssl_activated_recommended_features_extendify', true );
    }

    add_action( 'admin_init', 'rsssl_maybe_activate_recommended_features_extendify', 99 );
}
