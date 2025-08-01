<?php
/**
 * Plugin Name: Really Simple Security
 * Plugin URI: https://really-simple-ssl.com
 * Description: Easily improve site security with WordPress Hardening, Two-Factor Authentication (2FA), Login Protection, Vulnerability Detection and SSL certificate generation.
 * Version: 9.4.3
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

if (!function_exists('rsssl_activation_check')) {
    function rsssl_activation_check()
    {
        update_option('rsssl_activation', true, false );
        update_option('rsssl_show_onboarding', true, false );
        update_option('rsssl_redirect_to_settings_page', true, HOUR_IN_SECONDS );
    }
    register_activation_hook( __FILE__, 'rsssl_activation_check' );
}

if ( class_exists('REALLY_SIMPLE_SSL') ) {
    // Normally we can assume the function exists as class REALLY_SIMPLE_SSL
    // also exists. But as this function is new we should be extra sure.
    if (!function_exists('rsssl_deactivate_alternate')) {
        $rsssl_path = trailingslashit( plugin_dir_path( __FILE__ ) );
        require_once $rsssl_path . 'functions.php';
    }

    rsssl_deactivate_alternate('pro');
} else {
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

        private function __construct()
        {
            if (isset($_GET['rsssl_apitoken']) && $_GET['rsssl_apitoken'] == get_option('rsssl_csp_report_token') ) {
                if ( !defined('RSSSL_LEARNING_MODE') ) define( 'RSSSL_LEARNING_MODE' , true );
            }
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
                    self::$instance->onboarding = new rsssl_onboarding();
                    self::$instance->progress = new rsssl_progress();
                    self::$instance->certificate = new rsssl_certificate();
                    self::$instance->site_health = new rsssl_site_health();
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
            define('rsssl_plugin', plugin_basename(__FILE__));
            if ( !defined('rsssl_file') ){
                define('rsssl_file', __FILE__);
            }
            define('rsssl_version', '9.4.3');
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
                require_once( rsssl_path . 'compatibility.php');
                require_once( rsssl_path . 'upgrade.php');
                require_once( rsssl_path . 'settings/settings.php' );
                require_once( rsssl_path . 'modal/modal.php' );
                require_once( rsssl_path . 'onboarding/class-onboarding.php' );
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
     * @return bool
     */
    function rsssl_user_can_manage() {
        if ( current_user_can('manage_security') ) {
            return true;
        }

        #allow wp-cli access to activate ssl
        if ( defined( 'WP_CLI' ) && WP_CLI ){
            return true;
        }

        return false;
    }
}

if ( !function_exists('rsssl_admin_logged_in')){
    function rsssl_admin_logged_in(){
        $wpcli = defined( 'WP_CLI' ) && WP_CLI;
        return (is_admin() && rsssl_user_can_manage()) || rsssl_is_logged_in_rest() ||  wp_doing_cron() || $wpcli || defined('RSSSL_DOING_SYSTEM_STATUS') || defined('RSSSL_LEARNING_MODE');
    }
}



if ( ! function_exists( 'rsssl_is_logged_in_rest' ) ) {
	function rsssl_is_logged_in_rest() {
		// Check if the request URI is valid
		if (!isset($_SERVER['REQUEST_URI'])) {
			return false;
		}

		$request_uri = $_SERVER['REQUEST_URI'];

		// Check for a direct REST API path
		if (strpos($request_uri, '/reallysimplessl/v1/') !== false) {
			return is_user_logged_in();
		}

		// Check for rest_route parameter with reallysimplessl (plain permalinks)
		if (strpos($request_uri, 'rest_route=') !== false &&
		    strpos($request_uri, 'reallysimplessl') !== false) {
			return is_user_logged_in();
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
