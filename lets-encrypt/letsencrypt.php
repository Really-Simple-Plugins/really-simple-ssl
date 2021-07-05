<?php
defined('ABSPATH') or die();
/**
 * Capability handling for Let's Encrypt
 * @return bool
 *
 * php -r "readfile('https://getcomposer.org/installer');" | php
 */
if (!function_exists('rsssl_letsencrypt_generation_allowed')) {
	function rsssl_letsencrypt_generation_allowed() {

		if ( wp_doing_cron() ) {
			return true;
		}

		if ( !current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( isset($_GET['tab']) && $_GET['tab'] === 'letsencrypt' ){
			return true;
		}

		if ( isset($_GET['action']) && $_GET['action'] === 'rsssl_installation_progress' ){
			return true;
		}

		if ( isset($_POST['rsssl_le_nonce']) && wp_verify_nonce( $_POST['rsssl_le_nonce'], 'rsssl_save' )){
			return true;
		}

		return false;
	}
}

if ( rsssl_letsencrypt_generation_allowed() ) {

	class RSSSL_LETSENCRYPT {
		private static $instance;

		public $wizard;
		public $field;
		public $config;
		public $letsencrypt_handler;

		private function __construct() {

		}

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RSSSL_LETSENCRYPT ) ) {
				self::$instance = new RSSSL_LETSENCRYPT;
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->field               = new rsssl_field();
				self::$instance->wizard              = new rsssl_wizard();
				self::$instance->config              = new rsssl_config();
				if (version_compare(PHP_VERSION, rsssl_le_php_version, '>')) {
					self::$instance->letsencrypt_handler = new rsssl_letsencrypt_handler();
				}
			}

			return self::$instance;
		}

		private function setup_constants() {
			define('rsssl_le_php_version', '7.1');
			define('rsssl_le_url', plugin_dir_url(__FILE__));
			define('rsssl_le_path', trailingslashit(plugin_dir_path(__FILE__)));
			define('rsssl_le_wizard_path', trailingslashit(plugin_dir_path(__FILE__)).'/wizard/');
		}

		private function includes() {
			require_once( rsssl_le_path . 'cron.php' );
			require_once( rsssl_le_path . 'wizard/assets/icons.php' );
			require_once( rsssl_le_path . 'wizard/class-field.php' );
			require_once( rsssl_le_path . 'wizard/class-wizard.php' );
			require_once( rsssl_le_path . 'wizard/config/class-config.php' );
			require_once( rsssl_le_path . 'functions.php');

			if (version_compare(PHP_VERSION, rsssl_le_php_version, '>=')) {
				require_once( rsssl_le_path . 'wizard/notices.php' );
				require_once( rsssl_le_path . 'class-letsencrypt-handler.php' );
				require_once( rsssl_le_path . 'integrations/integrations.php' );
			}

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
}


class RSSSL_RESPONSE
{
	public $message;
	public $action;
	public $status;
	public $output;

	public function __construct($status, $action, $message, $output = false )
	{
		$this->status = $status;
		$this->action = $action;
		$this->message = $message;
		$this->output = $output;
	}

}


