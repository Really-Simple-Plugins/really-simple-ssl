<?php
defined('ABSPATH') or die();
/**
 * Capability handling for Let's Encrypt
 * @return bool
 *
 * php -r "readfile('https://getcomposer.org/installer');" | php
 */
if (!function_exists('rsssl_letsencrypt_generation_allowed')) {
	function rsssl_letsencrypt_generation_allowed($strict = false) {
		/**
		 * LE classes should also run if SSL is generated by rsssl, and the plus one cache is cleared.
		 */
		if ( get_option( 'rsssl_le_certificate_generated_by_rsssl' ) && !get_option('rsssl_plusone_count')  ) {
			return true;
		}

		if ( get_option( 'rsssl_le_certificate_generated_by_rsssl' ) && wp_doing_cron() ) {
			return true;
		}

		if ( !current_user_can( 'manage_security' ) ) {
			return false;
		}

		if ( isset($_GET['letsencrypt'])) {
			return true;
		}
		return false;
	}
}

class RSSSL_LETSENCRYPT {
	private static $instance;

	public $le_restapi;
	public $field;
	public $hosts;
	public $letsencrypt_handler;

	private function __construct() {

	}

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RSSSL_LETSENCRYPT ) ) {
			error_log("load Le instance");
			self::$instance = new RSSSL_LETSENCRYPT;
			self::$instance->setup_constants();
			self::$instance->includes();
			if (rsssl_letsencrypt_generation_allowed() ) {
				error_log("generation allowed instance");
				self::$instance->hosts = new rsssl_le_hosts();

				self::$instance->letsencrypt_handler = new rsssl_letsencrypt_handler();
				self::$instance->le_restapi = new rsssl_le_restapi();
			}
		}

		return self::$instance;
	}

	private function setup_constants() {
		define('rsssl_le_url', plugin_dir_url(__FILE__));
		define('rsssl_le_path', trailingslashit(plugin_dir_path(__FILE__)));
	}

	private function includes() {
		require_once( rsssl_le_path . 'functions.php');

		if ( rsssl_letsencrypt_generation_allowed() ) {
			require_once( rsssl_le_path . 'config/class-hosts.php' );
			require_once( rsssl_le_path . 'config/fields.php');
			require_once( rsssl_le_path . 'class-le-restapi.php' );
			require_once( rsssl_le_path . 'class-letsencrypt-handler.php' );
			require_once( rsssl_le_path . 'integrations/integrations.php' );
		}
		require_once( rsssl_le_path . 'config/notices.php' );

	}

	/**
	 * Notice about possible compatibility issues with add ons
	 */
	public static function admin_notices() {

	}
}

function RSSSL_LE() {
	return RSSSL_LETSENCRYPT::instance();
}

add_action( 'plugins_loaded', 'RSSSL_LE', 9 );


class RSSSL_RESPONSE
{
	public $message;
	public $action;
	public $status;
	public $output;
	public $request_success;

	public function __construct($status, $action, $message, $output = false )
	{
		$this->status = $status;
		$this->action = $action;
		$this->message = $message;
		$this->output = $output;
		$this->request_success = true;
	}

}