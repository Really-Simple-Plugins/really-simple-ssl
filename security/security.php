<?php
defined('ABSPATH') or die();
class REALLY_SIMPLE_SECURITY
{
	private static $instance;
	public $firewall_manager;
	public $hardening;
	/**
	 * Components array, so we can access singleton classes which are dynamically added, from anywhere.
	 * @var
	 */
	public $components;

	private function __construct()
	{
        if (!defined('RSSSL_SAFE_MODE') && file_exists(trailingslashit(WP_CONTENT_DIR) . 'rsssl-safe-mode.lock')) {
            define('RSSSL_SAFE_MODE', true);
        }
	}

	public static function instance()
	{
		if (!isset(self::$instance) && !(self::$instance instanceof REALLY_SIMPLE_SECURITY)) {
			self::$instance = new REALLY_SIMPLE_SECURITY;
			self::$instance->includes();
			if ( rsssl_admin_logged_in() ) {
				self::$instance->firewall_manager = new rsssl_firewall_manager();
				self::$instance->hardening = new rsssl_hardening();
			}
		}
		return self::$instance;
	}

	private function includes()
	{

		$path = rsssl_path.'security/';
		require_once( $path . 'integrations.php' );
		require_once( $path . 'hardening.php' );
		require_once( $path . 'cron.php' );
		require_once( $path . 'includes/check404/class-rsssl-simple-404-interceptor.php' );

		/**
		 * Load only on back-end
		 */
		if ( rsssl_admin_logged_in() ) {
			require_once( $path . 'functions.php' );
			require_once( $path . 'deactivate-integration.php' );
			require_once( $path . 'firewall-manager.php' );
			require_once( $path . 'tests.php' );
			require_once( $path . 'notices.php' );
			require_once( $path . 'sync-settings.php' );
		}

	}
}

function RSSSL_SECURITY()
{
	return REALLY_SIMPLE_SECURITY::instance();
}
add_action('plugins_loaded', 'RSSSL_SECURITY', 9);