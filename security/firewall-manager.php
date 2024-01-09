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

		if (!defined('WF_IS_WP_ENGINE')) {
			define('WF_IS_WP_ENGINE', isset($_SERVER['IS_WPE']));
		}
		if (!defined('WF_IS_FLYWHEEL')) {
			define('WF_IS_FLYWHEEL', isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Flywheel/') === 0);
		}
		if (!defined('WF_IS_PRESSABLE')) {
			define('WF_IS_PRESSABLE', (defined('IS_ATOMIC') && IS_ATOMIC) || (defined('IS_PRESSABLE') && IS_PRESSABLE));
		}

		$this->performInstallation();
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
	 * Get the path to our advanced-headers file
	 *
	 * @return string
	 */

	private function get_advanced_headers_path(){
		return WP_CONTENT_DIR . '/advanced-headers.php';
	}

	/**
	 * Generate security rules, and advanced-headers file
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
        $advanced_headers_file = $this->get_advanced_headers_path();

		$rules    = apply_filters('rsssl_firewall_rules', '');
		//no rules? remove the file
		if ( empty(trim($rules) ) ) {
			if ( file_exists($advanced_headers_file) ) {
				unlink($advanced_headers_file);
			}
			return;
		}

		$contents = '<?php' . "\n";
		$contents .= '/**' . "\n";
		$contents .= '* This file is created by Really Simple SSL' . "\n";
		$contents .= '*/' . "\n\n";
		$contents .= "defined('ABSPATH') or die();" . "\n\n";
		//allow disabling of headers for detection purposes
		$contents .= 'if ( isset($_GET["rsssl_header_test"]) && (int) $_GET["rsssl_header_test"] ===  ' . $this->get_headers_nonce() . ' ) return;' . "\n\n";
		$contents .= 'if (!defined("RSSSL_HEADERS_ACTIVE")) define("RSSSL_HEADERS_ACTIVE", true);'."\n";
		$contents .= "//RULES START\n".$rules;

		// write to advanced-header.php file
		if ( is_writable( WP_CONTENT_DIR ) ) {
			file_put_contents( $advanced_headers_file, $contents );
		}
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$wpconfig_path = $this->find_wp_config_path();
		$wpconfig = $wp_filesystem->get_contents($wpconfig_path);

		if ( $wp_filesystem->is_writable( $wpconfig_path ) && strpos( $wpconfig, 'advanced-headers.php' ) === false ) {
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
			$wp_filesystem->put_contents( $wpconfig_path, $updated );
		}

		//save errors
		if ( $wp_filesystem->is_writable( WP_CONTENT_DIR ) && ($wp_filesystem->is_writable( $wpconfig_path ) || strpos( $wpconfig, 'advanced-headers.php' ) !== false ) ) {
			update_option('rsssl_firewall_error', false, false );
		} else {
			if ( !$wp_filesystem->is_writable( $wpconfig_path ) ) {
				update_option('rsssl_firewall_error', 'wpconfig-notwritable', false );
			} else if ( !$wp_filesystem->is_writable( WP_CONTENT_DIR )) {
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
			if (file_exists($dir . "/wp-config.php")) {
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
		$file = $this->get_advanced_headers_path();
		$wpconfig_path = $this->find_wp_config_path();
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;
		if ( $wp_filesystem->is_writable( $wpconfig_path ) ) {
			$wpconfig = $wp_filesystem->get_contents( $wpconfig_path );
			$rule = "if ( file_exists('" . $file . "') ) { " . "\n";
			$rule .= "\t" . "require_once '$file';" . "\n" . "}";
			if ( strpos( $wpconfig, $rule ) !== false ) {
				$updated_wpconfig = str_replace( $rule, '', $wpconfig );
				$wp_filesystem->put_contents( $wpconfig_path, $updated_wpconfig );
			}
		}

		if ( $wp_filesystem->is_file( $file ) ) {
			$wp_filesystem->delete($file);
		}
	}

	public function getHomePath() {
		if (!function_exists('get_home_path')) {
			include_once(ABSPATH . 'wp-admin/includes/file.php');
		}
		if (WF_IS_FLYWHEEL)
			return trailingslashit($_SERVER['DOCUMENT_ROOT']);
		return get_home_path();
	}

	/**
	 * Get the path to the user.ini file
	 *
	 * @return false|string
	 */
	public function getUserIniPath() {
		$userIni = ini_get('user_ini.filename');
		if ($userIni) {
			return $this->getHomePath() . $userIni;
		}
		return false;
	}

	/**
	 *
	 * @throws wfWAFAutoPrependHelperException
	 */
	public function performInstallation() {
		$server_config = RSSSL()->server->auto_prepend_config();

		global $wp_filesystem;
		$bootstrapPath = $this->get_advanced_headers_path();

		$htaccessPath = RSSSL()->admin->htaccess_file();
		$homePath = dirname($htaccessPath);

		$userIniPath = $this->getUserIniPath();
		$userIni = ini_get('user_ini.filename');

		error_log($server_config);
		error_log($userIniPath);
		error_log($userIni);

		return;
		$userIniHtaccessDirectives = '';
		if ($userIni) {
			$userIniHtaccessDirectives = sprintf('<Files "%s">
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
</Files>
', addcslashes($userIni, '"'));
		}

		// .htaccess configuration
		switch ($server_config) {
			case 'apache-mod_php':
				$autoPrependDirective = sprintf("# Really Simple SSL WAF
<IfModule mod_php7.c>
	php_value auto_prepend_file '%1\$s'
</IfModule>
<IfModule mod_php.c>
	php_value auto_prepend_file '%1\$s'
</IfModule>
$userIniHtaccessDirectives
# END Really Simple SSL WAF
", addcslashes($bootstrapPath, "'"));
				break;

			case 'litespeed':
				$escapedBootstrapPath = addcslashes($bootstrapPath, "'");
				$autoPrependDirective = sprintf("# Really Simple SSL WAF
<IfModule LiteSpeed>
php_value auto_prepend_file '%s'
</IfModule>
<IfModule lsapi_module>
php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END Really Simple SSL WAF
", $escapedBootstrapPath, $escapedBootstrapPath);
				break;

			case 'apache-suphp':
				$autoPrependDirective = sprintf("# Really Simple SSL WAF
$userIniHtaccessDirectives
# END Really Simple SSL WAF
", addcslashes($homePath, "'"));
				break;

			case 'cgi':
				if ($userIniHtaccessDirectives) {
					$autoPrependDirective = sprintf("# Really Simple SSL WAF
$userIniHtaccessDirectives
# END Really Simple SSL WAF
", addcslashes($homePath, "'"));
				}
				break;
		}

		if (!empty($autoPrependDirective)) {
			// Modify .htaccess
			$htaccessContent = $wp_filesystem->get_contents($htaccessPath);

			if ($htaccessContent) {
				$regex = '/# Really Simple SSL .*?# END Really Simple SSL WAF/is';
				if (preg_match($regex, $htaccessContent, $matches)) {
					$htaccessContent = preg_replace($regex, $autoPrependDirective, $htaccessContent);
				} else {
					$htaccessContent .= "\n\n" . $autoPrependDirective;
				}
			} else {
				$htaccessContent = $autoPrependDirective;
			}

			if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
				throw new wfWAFAutoPrependHelperException(__('We were unable to make changes to the .htaccess file. It\'s possible WordPress cannot write to the .htaccess file because of file permissions, which may have been set by another security plugin, or you may have set them manually. Please verify the permissions allow the web server to write to the file, and retry the installation.', 'wordfence'));
			}
			if ($server_config === 'litespeed') {
				// sleep(2);
				$wp_filesystem->touch($htaccessPath);
			}

		}
		if ($userIni) {
			// .user.ini configuration
			switch ($server_config) {
				case 'cgi':
				case 'nginx':
				case 'apache-suphp':
				case 'litespeed':
				case 'iis':
					$autoPrependIni = sprintf("; Really Simple SSL WAF
auto_prepend_file = '%s'
; END Really Simple SSL WAF
", addcslashes($bootstrapPath, "'"));

					break;
			}

			if ( !empty($autoPrependIni) ) {

				// Modify .user.ini
				$userIniContent = $wp_filesystem->get_contents($userIniPath);
				if (is_string($userIniContent)) {
					$userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
					$regex = '/; Really Simple SSL WAF.*?; END Really Simple SSL WAF/is';
					if (preg_match($regex, $userIniContent, $matches)) {
						$userIniContent = preg_replace($regex, $autoPrependIni, $userIniContent);
					} else {
						$userIniContent .= "\n\n" . $autoPrependIni;
					}
				} else {
					$userIniContent = $autoPrependIni;
				}

				if ( !$wp_filesystem->put_contents($userIniPath, $userIniContent) ) {
					//RSSSL_SECURITY()->error_handler->add('waf-not-writable');
				}
			}
		}
	}
}
