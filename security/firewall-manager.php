<?php
defined('ABSPATH') or die();
class rsssl_firewall_manager {
	private static $_this;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}
		self::$_this = $this;
		//trigger this action to force rules update
		add_action( 'rsssl_update_rules', array($this, 'insert_advanced_header_file'), 10 );
		add_action( 'rsssl_after_saved_fields', array($this, 'insert_advanced_header_file'), 100 );
		add_action( 'rsssl_deactivate', array($this, 'remove_advanced_headers'), 20 );
		add_filter( 'rsssl_notices', array($this, 'notices') );
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

		if ( isset($_GET["page"]) && $_GET["page"] === "really-simple-security" ) {
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
			update_site_option("rsssl_header_detection_nonce", rand(1000, 999999999) );
		}
		return (int) get_site_option("rsssl_header_detection_nonce");
	}

	/**
	 * Check if any rules were added
	 * @return bool
	 */
	public function has_rules(){
		$rules    = apply_filters('rsssl_firewall_rules', '');
		return !empty(trim($rules));
	}

	/**
	 * Generate security rules, and advanced-headers.php file
	 *
	 */
	public function insert_advanced_header_file() {
		if ( !rsssl_user_can_manage() && !defined( 'RSSSL_LEARNING_MODE' ) ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		$use_dynamic_path = WP_CONTENT_DIR === ABSPATH . 'wp-content';
        $advanced_headers_file = WP_CONTENT_DIR . '/advanced-headers.php';

		$rules    = apply_filters('rsssl_firewall_rules', '');
		//no rules? remove the file
		if ( empty(trim($rules) ) ) {
			if ( file_exists($advanced_headers_file) ) {
				if ( !defined('RSSSL_SAFE_MODE')) unlink($advanced_headers_file);
			}
			return;
		}

		$contents = '<?php' . "\n";
		$contents .= '/**' . "\n";
		$contents .= '* This file is created by Really Simple SSL' . "\n";
		$contents .= '*/' . "\n\n";
		//allow disabling of headers for detection purposes
		$contents .= 'if ( isset($_GET["rsssl_header_test"]) && (int) $_GET["rsssl_header_test"] ===  ' . $this->get_headers_nonce() . ' ) return;' . "\n\n";
		$contents .= 'if (!defined("RSSSL_HEADERS_ACTIVE")) define("RSSSL_HEADERS_ACTIVE", true);'."\n";
		$contents .= "//RULES START\n".$rules;

		// write to advanced-header.php file
		if ( is_writable( WP_CONTENT_DIR ) ) {
			file_put_contents( $advanced_headers_file, $contents );
		}

		$wpconfig_path = $this->find_wp_config_path();
		$wpconfig      = file_get_contents( $wpconfig_path );
		if ( is_writable( $wpconfig_path ) && strpos( $wpconfig, 'advanced-headers.php' ) === false ) {
			// As WP_CONTENT_DIR is not defined at this point in the wp-config, we can't use that.
			// for those setups where the WP_CONTENT_DIR is not in the default location, we hardcode the path.
            if ( $use_dynamic_path ) {
                $rule = 'if (file_exists( ABSPATH . "wp-content/advanced-headers.php")) {' . "\n";
                $rule .= "\t" . 'require_once ABSPATH . "wp-content/advanced-headers.php";' . "\n" . '}';
            } else {
                $rule = 'if (file_exists(\'' . WP_CONTENT_DIR . '/advanced-headers.php\')) {' . "\n";
                $rule .= "\t" . 'require_once \'' . WP_CONTENT_DIR . '/advanced-headers.php\';' . "\n" . '}';
            }

            //if RSSSL comment is found, insert after
			$rsssl_comment = '//END Really Simple SSL Server variable fix';
			if ( strpos($wpconfig, $rsssl_comment)!==false ) {
				$pos = strrpos($wpconfig, $rsssl_comment);
				$updated = substr_replace($wpconfig, $rsssl_comment."\n" . $rule . "\n", $pos, strlen($rsssl_comment));
			} else {
				$updated = preg_replace( '/' . '<\?php' . '/', '<?php' . "\n" . $rule . "\n", $wpconfig, 1 );
			}
			file_put_contents( $wpconfig_path, $updated );
		}

		//save errors
		if ( is_writable( WP_CONTENT_DIR ) && (is_writable( $wpconfig_path ) || strpos( $wpconfig, 'advanced-headers.php' ) !== false ) ) {
			update_option('rsssl_firewall_error', false, false );
		} else {
			if ( !is_writable( $wpconfig_path ) ) {
				update_option('rsssl_firewall_error', 'wpconfig-notwritable', false );
			} else if ( !is_writable( WP_CONTENT_DIR )) {
				update_option('rsssl_firewall_error', 'advanced-headers-notwritable', false );
			}
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
	 * Get the status for the firewall
	 *
	 * @return bool
	 */
	public function firewall_active_error(){
		if (!$this->has_rules()) {
			return false;
		}
		return !defined('RSSSL_HEADERS_ACTIVE');
	}

	/**
	 * Show some notices
	 * @param array $notices
	 *
	 * @return array
	 */
	public function notices( $notices ) {
		$notices['firewall-error'] = array(
			'callback' => 'RSSSL_SECURITY()->firewall_manager->firewall_write_error',
			'score' => 5,
			'output' => array(
				'wpconfig-notwritable' => array(
					'title' => __("Firewall", "really-simple-ssl"),
					'msg' => __("A firewall rule was enabled, but the wp-config.php is not writable.", "really-simple-ssl").' '.__("Please set the wp-config.php to writable until the rule has been written.", "really-simple-ssl"),
					'icon' => 'open',
					'dismissible' => true,
				),
				'advanced-headers-notwritable' => array(
						'title' => __("Firewall", "really-simple-ssl"),
						'msg' => __("A firewall rule was enabled, but /the wp-content/ folder is not writable.", "really-simple-ssl").' '.__("Please set the wp-content folder to writable:", "really-simple-ssl"),
					'icon' => 'open',
					'dismissible' => true,
				),
			),
			'show_with_options' => [
				'disable_http_methods',
			]
		);
		$notices['firewall-active'] = array(
			'condition' => ['RSSSL_SECURITY()->firewall_manager->firewall_active_error'],
			'callback' => '_true_',
			'score' => 5,
			'output' => array(
				'true' => array(
					'title' => __("Firewall", "really-simple-ssl"),
					'msg' => __("A firewall rule was enabled, but the firewall does not seem to get loaded correctly.", "really-simple-ssl").' '.__("Please check if the advanced-headers.php file is included in the wp-config.php, and exists in the wp-content folder.", "really-simple-ssl"),
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

	/**
	 * Admin is not always loaded here, so we define our own function
	 * @return string|null
	 */
	public function find_wp_config_path()
	{
		//limit nr of iterations to 5
		$i = 0;
		$maxiterations = 5;
		$dir = ABSPATH;
		do {
			$i++;
			if ( file_exists($dir . "/wp-config.php") ) {
				return $dir . "/wp-config.php";
			}
		} while (($dir = realpath("$dir/..")) && ($i < $maxiterations));
		return null;
	}

	/**
	 * Clear the headers
	 * @return void
	 */
	public function remove_advanced_headers() {
		if ( !rsssl_user_can_manage() ) {
			return;
		}
		$file = ABSPATH . 'wp-content/advanced-headers.php';
		$wpconfig_path = $this->find_wp_config_path();
		if ( is_writable( $wpconfig_path ) ) {
			$wpconfig = file_get_contents( $wpconfig_path );
			$rule = "if ( file_exists('" . $file . "') ) { " . "\n";
			$rule .= "\t" . "require_once '$file';" . "\n" . "}";
			if ( strpos( $wpconfig, $rule ) !== false ) {
				$updated_wpconfig = str_replace( $rule, '', $wpconfig );
				file_put_contents( $wpconfig_path, $updated_wpconfig );
			}
		}

		if ( file_exists( $file ) ) {
			if ( !defined('RSSSL_SAFE_MODE')) unlink( $file );
		}
	}
}
