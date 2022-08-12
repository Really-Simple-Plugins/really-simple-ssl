<?php
class rsssl_firewall {
	private static $_this;
	public $cors_headers;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		self::$_this = $this;

		add_action( 'plugins_loaded', array($this, 'insert_advanced_header_file'), 10 );
		add_action( 'rsssl_after_saved_fields', array($this, 'insert_advanced_header_file'), 20 );
	}

	static function this() {
		return self::$_this;
	}

	/**
	 * This class has it's own settings page, to ensure it can always be called
	 *
	 * @return bool
	 */
	public function is_settings_page()
	{
		if ( rsssl_is_logged_in_rest() ){
			return true;
		}

		if (isset($_GET["page"]) && ($_GET["page"] == "rlrsssl_really_simple_ssl" || $_GET["page"] == "really-simple-ssl") ) {
			return true;
		}

		return false;
	}

	/**
	 * Generate and return a random nonce
	 * @return int
	 */
	public function get_headers_nonce(){
		if ( !get_site_option("rsssl_header_detection_nonce")) {
			update_site_option("rsssl_header_detection_nonce", rand(1000, 999999999) , false);
		}
		return get_site_option("rsssl_header_detection_nonce");
	}

	/**
	 *
	 * Generate security rules, and advanced-headers.php file
	 *
	 * @param bool $force
	 */
	public function insert_advanced_header_file( $force = false ) {
		$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
		$wpconfig      = file_get_contents( $wpconfig_path );
		if (
			( ! $force && ! $this->is_settings_page() )
			|| wp_doing_ajax()
			|| !current_user_can( "manage_options" )
		) {
			return;
		}

		$dir  = ABSPATH . 'wp-content';
		$file = $dir . '/advanced-headers.php';

		$rules    = apply_filters('rsssl_firewall', '');
		//no rules? remove the file
		if ( empty(trim($rules) ) ) {
			if ( file_exists($file) ) {
				unlink($file);
			}
			return;
		}

		$contents = '<?php' . "\n";
		$contents .= '/**' . "\n";
		$contents .= '* This file is created by Really Simple SSL' . "\n";
		$contents .= '*/' . "\n\n";
		$contents .= "defined('ABSPATH') or die();" . "\n\n";
		//allow disabling of headers for detection purposes
		$contents .= 'if ( isset($_GET["rsssl_header_test"]) && intval($_GET["rsssl_header_test"]) ===  ' . $this->get_headers_nonce() . ' ) return;' . "\n\n";
		$contents .= "//RULES START\n".$rules;

		// write to advanced-header.php file
		if ( is_writable( $dir ) ) {
			file_put_contents( $file, $contents );
		} else {
			update_option('rsssl_firewall_error', 'advanced-headers-notwritable', false );
		}
		if ( !is_writable( $wpconfig_path ) ) {
			update_option('rsssl_firewall_error', 'wpconfig-notwritable', false );
		} else if ( strpos( $wpconfig, 'advanced-headers.php' ) === false ) {
			$rule    = "if ( file_exists('" . $file . "') ) { " . "\n";
			$rule    .= "\t" . "require_once '$file';" . "\n" . "}";
			$updated = preg_replace( '/' . '<\?php' . '/', '<?php' . "\n" . $rule . "\n", $wpconfig, 1 );
			file_put_contents( $wpconfig_path, $updated );
		}
	}
}