<?php
class rsssl_firewall {
	private static $_this;
	public $cors_headers;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		self::$_this = $this;
		add_action( 'rsssl_update_rules', array($this, 'insert_advanced_header_file'), 10 );
		add_action( 'rsssl_after_saved_fields', array($this, 'insert_advanced_header_file'), 100 );
		add_filter('rsssl_notices', array($this, 'notices') );
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
	 * Generate security rules, and advanced-headers.php file
	 *
	 */
	public function insert_advanced_header_file() {

		if ( wp_doing_ajax() ) {
			return;
		}
		$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
		$wpconfig      = file_get_contents( $wpconfig_path );

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

		//save errors
		if ( is_writable( $dir ) && (is_writable( $wpconfig_path ) || strpos( $wpconfig, 'advanced-headers.php' ) !== false ) ) {
			update_option('rsssl_firewall_error', false, false );
		} else {
			if ( !is_writable( $wpconfig_path ) ) {
				update_option('rsssl_firewall_error', 'wpconfig-notwritable', false );
			} else if ( !is_writable( $dir )) {
				update_option('rsssl_firewall_error', 'advanced-headers-notwritable', false );
			}
		}

		// write to advanced-header.php file
		if ( is_writable( $dir ) ) {
			file_put_contents( $file, $contents );
		}

		if ( is_writable( $wpconfig_path ) && strpos( $wpconfig, 'advanced-headers.php' ) === false ) {
			$rule    = "if ( file_exists('" . $file . "') ) { " . "\n";
			$rule    .= "\t" . "require_once '$file';" . "\n" . "}";
			$updated = preg_replace( '/' . '<\?php' . '/', '<?php' . "\n" . $rule . "\n", $wpconfig, 1 );
			file_put_contents( $wpconfig_path, $updated );
		}
	}

	/**
	 * Get the status for the firewall rules writing
	 *
	 * @return false|string
	 */
	public function firewall_write_error(){
		return get_site_option('rsssl_firewall_error');
	}

	/**
	 * Show some notices
	 * @param array $notices
	 *
	 * @return array
	 */
	public function notices( $notices ) {
		$notices['firewall-error'] = array(
			'callback' => 'RSSSL_SECURITY()->firewall->firewall_write_error',
			'score' => 5,
			'output' => array(
				'wpconfig-notwritable' => array(
					'msg' => __("A firewall rule was enabled, but the wp-config.php is not writable.", "really-simple-ssl").' '.__("Please set the wp-config.php to writable until the rule has been written.", "really-simple-ssl"),
					'icon' => 'open',
					'dismissible' => true,
				),
				'advanced-headers-notwritable' => array(
					'msg' => __("A firewall rule was enabled, but the wp-content/ folder is not writable.", "really-simple-ssl").' '.__("Please set the wp-content folder to writable:", "really-simple-ssl"),
					'icon' => 'open',
					'dismissible' => true,
				),
			),
			'show_with_options' => [
				'disable_http_methods',
			]
		);
		return $notices;
	}
}