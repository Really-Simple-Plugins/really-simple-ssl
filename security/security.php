<?php
defined('ABSPATH') or die();
class REALLY_SIMPLE_SECURITY
{
	private static $instance;
	public $firewall_manager;
	public $hardening;
	public $error_handler;

	private function __construct()
	{

	}

	public static function instance()
	{
		if (!isset(self::$instance) && !(self::$instance instanceof REALLY_SIMPLE_SECURITY)) {
			self::$instance = new REALLY_SIMPLE_SECURITY;
			self::$instance->includes();
			if ( rsssl_admin_logged_in() ) {
				self::$instance->firewall_manager = new rsssl_firewall_manager();
				self::$instance->hardening = new rsssl_hardening();
				self::$instance->error_handler = new rsssl_error_handler();
			}
		}
		return self::$instance;
	}

	private function includes()
	{
		$path = rsssl_path.'security/';
		require_once( $path . 'cron.php' );
		require_once( $path . 'integrations.php' );
		require_once( $path . 'hardening.php' );

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
			require_once( $path . 'class-error-handler.php' );
		}

	}
}

function RSSSL_SECURITY()
{
	return REALLY_SIMPLE_SECURITY::instance();
}
add_action('plugins_loaded', 'RSSSL_SECURITY', 9);