<?php
defined( 'ABSPATH' ) || die();

/**
 * Class to handle the creation and include of the firewall
 */
class rsssl_firewall_manager {
	/**
	 * Firewall object
	 *
	 * @var rsssl_firewall_manager
	 */
	private static $this;
	/**
	 * File
	 *
	 * @var string
	 */
	private $file = WP_CONTENT_DIR . '/advanced-headers.php';
	/**
	 * If we can use a dynamic path
	 *
	 * @var bool
	 */
	private $use_dynamic_path = WP_CONTENT_DIR === ABSPATH . 'wp-content';

	/**
	 * Our constructor
	 */
	public function __construct() {
		if ( isset( self::$this ) ) {
			wp_die( 'this is a singleton class and you cannot create a second instance.' );
		}
		self::$this = $this;
		// trigger this action to force rules update
		add_action( 'rsssl_update_rules', array( $this, 'install' ), 10 );
		add_action( 'rsssl_after_saved_fields', array( $this, 'install' ), 100 );
		add_action( 'rsssl_deactivate', array( $this, 'uninstall' ), 20 );
		add_filter( 'rsssl_notices', array( $this, 'notices' ) );

		if ( ! defined( 'RSSSL_IS_WP_ENGINE' ) ) {
			define( 'RSSSL_IS_WP_ENGINE', isset( $_SERVER['IS_WPE'] ) );
		}
		if ( ! defined( 'RSSSL_IS_FLYWHEEL' ) ) {
			define( 'RSSSL_IS_FLYWHEEL', isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) === 0 );
		}
		if ( ! defined( 'RSSSL_IS_PRESSABLE' ) ) {
			define( 'RSSSL_IS_PRESSABLE', ( defined( 'IS_ATOMIC' ) && IS_ATOMIC ) || ( defined( 'IS_PRESSABLE' ) && IS_PRESSABLE ) );
		}
	}

	/**
	 * Main installer for the firewall file
	 *
	 * @return void
	 */
	public function install() {
		if ( ! rsssl_user_can_manage() && ! defined( 'RSSSL_LEARNING_MODE' ) ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		$rules = apply_filters( 'rsssl_firewall_rules', '' );
		// no rules? remove the file.
		if ( empty( trim( $rules ) ) ) {
			// $this->delete_file();
			$this->remove_prepend_file_in_htaccess();
			$this->remove_prepend_file_in_wpconfig();
			return;
		}

		// update the file to be included.
		$this->update_firewall( $rules );

		$this->include_prepend_file_in_wp_config();
		if ( $this->uses_htaccess() ) {
			$this->include_prepend_file_in_htaccess();
		}
	}

	/**
	 * Remove file and file inclusions
	 *
	 * @return void
	 */
	public function uninstall() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		$this->remove_prepend_file_in_htaccess();
		$this->remove_prepend_file_in_wp_config();

		$this->delete_file();
	}

	/**
	 * Check if our firewall file exists
	 *
	 * @param string $file // filename, including path
	 *
	 * @return bool
	 */
	private function file_exists( string $file ): bool {
		return file_exists( $file );
		// $wp_filesystem = $this->init_file_system();
		// return $wp_filesystem->is_file($this->file);
	}

	/**
	 * Initialize the WP_Filesyste
	 *
	 * @return false|WP_Filesystem_Base
	 */
	private function init_file_system() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( false === ( $creds = request_filesystem_credentials( site_url(), '', false, false, null ) ) ) {
			return false; // stop processing here.
		}
		global $wp_filesystem;
		if ( ! WP_Filesystem( $creds ) ) {
			// request_filesystem_credentials(site_url(), '', true, false, null);//phpcs:ingore
			return false;
		}
		return $wp_filesystem;
	}

	/**
	 * Update the file that contains the firewall rules, advanced-headers.php
	 *
	 * @param string $rules //rules to add to the firewall.
	 *
	 * @return void
	 */
	private function update_firewall( string $rules ): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$contents  = '<?php' . "\n";
		$contents .= '/**' . "\n";
		$contents .= '* This file is created by Really Simple SSL' . "\n";
		$contents .= '*/' . "\n\n";
		// allow disabling of headers for detection purposes.
		$contents .= 'if ( isset($_GET["rsssl_header_test"]) && (int) $_GET["rsssl_header_test"] ===  ' . $this->get_headers_nonce() . ' ) return;' . "\n\n";
		$contents .= 'if (!defined("RSSSL_HEADERS_ACTIVE")) define("RSSSL_HEADERS_ACTIVE", true);' . "\n";
		$contents .= "//RULES START\n" . $rules;

		$this->put_contents( $this->file, $contents );
	}

	/**
	 * Save data
	 *
	 * @param string $file //filename, including path.
	 * @param string $contents //data to save.
	 *
	 * @return void
	 */
	private function put_contents( $file, $contents ): void {
		// $wp_filesystem = $this->init_file_system();
		// $result = $wp_filesystem->put_contents($contents, $this->file);
		file_put_contents( $file, $contents );//phpcs:ignore
		chmod( $this->file, 0664 ); //phpcs:ignore
	}

	/**
	 * Get the contents of a file
	 *
	 * @param string $file //filename, including path.
	 *
	 * @return string
	 */
	private function get_contents( string $file ): string {
		if ( ! $this->file_exists( $file ) ) {
			return '';
		}
		// $wp_filesystem = $this->init_file_system();
		// $result = $wp_filesystem->get_contents($file);
		return file_get_contents( $file );//phpcs:ignore
	}
	/**
	 * Delete a file
	 *
	 * @return void
	 */
	private function delete_file(): void {
		if ( $this->file_exists( $this->file ) ) {
			unlink( $this->file );//phpcs:ignore
		}
		// $wp_filesystem = $this->init_file_system();
		// $wp_filesystem->delete($this->file);
	}

	/**
	 * Get the .htaccess path
	 *
	 * @return string
	 */
	private function htaccess_path(): string {
		return $this->get_home_path() . '.htaccess';
	}

	/**
	 * Get the home path
	 *
	 * @return string
	 */
	public function get_home_path(): string {
		if ( ! function_exists( 'get_home_path' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( RSSSL_IS_FLYWHEEL && isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			return trailingslashit( $this->sanitize_path( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) );
		}
		return get_home_path();
	}

	/**
	 * Sanitize a path
	 *
	 * @param string $path //string to sanitize.
	 *
	 * @return string
	 */
	private function sanitize_path( $path ) {
		// prevent path traversal.
		return str_replace( '../', '/', realpath( sanitize_text_field( $path ) ) );
	}

	/**
	 * Check if this server uses .htaccess. Not by checking the server header, but simply by checking
	 * if the htaccess file exists.
	 *
	 * @return bool
	 */
	private function uses_htaccess(): bool {
		return $this->file_exists( $this->htaccess_path() );
	}

	/**
	 * Include the prepend file in the .htaccess
	 *
	 * @return void
	 */
	private function include_prepend_file_in_htaccess(): void {
		if ( ! $this->file_exists( $this->file ) ) {
			return;
		}

		// check if the wp-config contains the if constant condition, to prevent duplicate loading. If not, try upgrading. If that fails, skip.
		if ( ! $this->wp_config_contains_latest() ) {
			return;
		}

		$rules           = $this->get_htaccess_rules();
		$start           = '#Begin Really Simple Auto Prepend File ' . "\n";
		$end             = "\n" . '#End Really Simple Auto Prepend File' . "\n";
		$pattern_content = '/' . $start . '(.*?)' . $end . '/is';
		$htaccess_file   = $this->htaccess_path();
		if ( $this->file_exists( $htaccess_file ) ) {
			$rules   = implode( "\n", $rules );
			$content = $this->get_contents( $htaccess_file );
			// remove first, to ensure we are at the top of the file.
			$content = preg_replace( $pattern_content, '', $content );
			if ( ! empty( $rules ) ) {
				if ( ! $this->is_writable( $htaccess_file ) ) {
					update_site_option( 'rsssl_htaccess_error', 'not-writable' );
					update_site_option( 'rsssl_htaccess_rules', $rules . get_site_option( 'rsssl_htaccess_rules' ) );
				} else {
					delete_site_option( 'rsssl_htaccess_error' );
					delete_site_option( 'rsssl_htaccess_rules' );
					// add rules as new block.
					$new_htaccess = $start . $rules . $end . $content;

					// clean up.
					if ( strpos( $new_htaccess, "\n\n\n" ) !== false ) {
						$new_htaccess = str_replace( "\n\n\n", "\n\n", $new_htaccess );
					}

					$this->put_contents( $htaccess_file, $new_htaccess );
				}
			}
		}
	}

	/**
	 * Get the .htaccess rules for the prepend file
	 *
	 * @return array //the array containing the lines of rules
	 */
	private function get_htaccess_rules() : array {
		return array(
			'<IfModule mod_php.c>',
			'php_value auto_prepend_file ' . $this->file,
			'</IfModule>',
		);
	}

	/**
	 * Include the file in the wp-config
	 *
	 * @return void
	 */
	private function include_prepend_file_in_wp_config(): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$file    = $this->wpconfig_path();
		$content = $this->get_contents( $file );
		if ( $this->is_writable( $file ) && strpos( $content, 'advanced-headers.php' ) === false ) {
			$rule = $this->get_wp_config_rule();

			// if RSSSL comment is found, insert after.
			$rsssl_comment = '//END Really Simple SSL Server variable fix';
			if ( strpos( $content, $rsssl_comment ) !== false ) {
				$pos     = strrpos( $content, $rsssl_comment );
				$updated = substr_replace( $content, $rsssl_comment . "\n" . $rule . "\n", $pos, strlen( $rsssl_comment ) );
			} else {
				$updated = preg_replace( '/<\?php/', "<?php\n" . $rule . "\n", $content, 1 );
			}

			if ( strpos( $updated, "\n\n\n" ) !== false ) {
				$updated = str_replace( "\n\n\n", "\n\n", $updated );
			}

			$this->put_contents( $file, $updated );
		}

		// save errors.
		if ( $this->is_writable( WP_CONTENT_DIR ) && ( $this->is_writable( $file ) || strpos( $content, 'advanced-headers.php' ) !== false ) ) {
			update_option( 'rsssl_firewall_error', false, false );
		} elseif ( ! $this->is_writable( $file ) ) {
				update_option( 'rsssl_firewall_error', 'wpconfig-notwritable', false );
		} elseif ( ! $this->is_writable( WP_CONTENT_DIR ) ) {
			update_option( 'rsssl_firewall_error', 'advanced-headers-notwritable', false );
		}
	}

	/**
	 * Clear the rules
	 *
	 * @return void
	 */
	private function remove_prepend_file_in_htaccess() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$start         = '#Begin Really Simple Auto Prepend File ' . "\n";
		$end           = "\n" . '#End Really Simple Auto Prepend File' . "\n";
		$pattern       = '/' . $start . '(.*?)' . $end . '/is';
		$htaccess_file = $this->htaccess_path();
		if ( $this->file_exists( $htaccess_file ) ) {
			$content = $this->get_contents( $htaccess_file );
			// remove first, to ensure we are at the top of the file.
			$content = preg_replace( $pattern, '', $content );
			$this->put_contents( $htaccess_file, $content );
		}
	}

	/**
	 * Remove the prepend file from the config
	 *
	 * @return void
	 */
	private function remove_prepend_file_in_wpconfig() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$file = $this->wpconfig_path();
		if ( $this->is_writable( $file ) ) {
			$content = $this->get_contents( $file );
			$rule    = $this->get_wp_config_rule();
			if ( strpos( $content, $rule ) !== false ) {
				$content = str_replace( $rule, '', $content );
				if ( strpos( $content, "\n\n\n" ) !== false ) {
					$content = str_replace( "\n\n\n", "\n\n", $content );
				}
				$this->put_contents( $file, $content );
			}
		}
	}

	/**
	 * Wrapper function
	 *
	 * @param string $file // filename, including path.
	 *
	 * @return bool
	 */
	private function is_writable( $file ): bool {
		// $wp_filesystem = $this->init_file_system();
		// return $wp_filesystem->is_writable($file);
		return is_writable( $file );//phpcs:ignore
	}

	/**
	 * This class has it's own settings page, to ensure it can always be called
	 *
	 * @return bool
	 */
	public function is_settings_page() {
		if ( rsssl_is_logged_in_rest() ) {
			return true;
		}

		if ( isset( $_GET['page'] ) && 'really-simple-security' === $_GET['page'] ) {//phpcs:ignore
			return true;
		}

		return false;
	}

	/**
	 * Generate and return a random nonce
	 *
	 * @return int
	 */
	public function get_headers_nonce() {
		if ( ! get_site_option( 'rsssl_header_detection_nonce' ) ) {
			update_site_option( 'rsssl_header_detection_nonce', wp_rand( 1000, 999999999 ) );
		}
		return (int) get_site_option( 'rsssl_header_detection_nonce' );
	}

	/**
	 * Check if any rules were added
	 *
	 * @return bool
	 */
	public function has_rules() {
		$rules = apply_filters( 'rsssl_firewall_rules', '' );
		return ! empty( trim( $rules ) );
	}

	/**
	 * Get the status for the firewall rules writing
	 *
	 * @return false|string
	 */
	public function firewall_write_error() {
		return get_site_option( 'rsssl_firewall_error' );
	}



	/**
	 * Get the status for the firewall
	 *
	 * @return bool
	 */
	public function firewall_active_error() {
		if ( ! $this->has_rules() ) {
			return false;
		}
		return ! defined( 'RSSSL_HEADERS_ACTIVE' );
	}

	/**
	 * Show some notices
	 *
	 * @param array $notices //array of notices.
	 *
	 * @return array
	 */
	public function notices( $notices ) {
		$notices['firewall-error']  = array(
			'callback'          => 'RSSSL_SECURITY()->firewall_manager->firewall_write_error',
			'score'             => 5,
			'output'            => array(
				'wpconfig-notwritable'         => array(
					'title'       => __( 'Firewall', 'really-simple-ssl' ),
					'msg'         => __( 'A firewall rule was enabled, but the wp-config.php is not writable.', 'really-simple-ssl' ) . ' ' . __( 'Please set the wp-config.php to writable until the rule has been written.', 'really-simple-ssl' ),
					'icon'        => 'open',
					'dismissible' => true,
				),
				'advanced-headers-notwritable' => array(
					'title'       => __( 'Firewall', 'really-simple-ssl' ),
					'msg'         => __( 'A firewall rule was enabled, but /the wp-content/ folder is not writable.', 'really-simple-ssl' ) . ' ' . __( 'Please set the wp-content folder to writable:', 'really-simple-ssl' ),
					'icon'        => 'open',
					'dismissible' => true,
				),
			),
			'show_with_options' => array(
				'disable_http_methods',
			),
		);
		$notices['firewall-active'] = array(
			'condition'         => array( 'RSSSL_SECURITY()->firewall_manager->firewall_active_error' ),
			'callback'          => '_true_',
			'score'             => 5,
			'output'            => array(
				'true' => array(
					'title'       => __( 'Firewall', 'really-simple-ssl' ),
					'msg'         => __( 'A firewall rule was enabled, but the firewall does not seem to get loaded correctly.', 'really-simple-ssl' ) . ' ' . __( 'Please check if the advanced-headers.php file is included in the wp-config.php, and exists in the wp-content folder.', 'really-simple-ssl' ),
					'icon'        => 'open',
					'dismissible' => true,
				),
			),
			'show_with_options' => array(
				'disable_http_methods',
			),
		);
		return $notices;
	}

	/**
	 * // As WP_CONTENT_DIR is not defined at this point in the wp-config, we can't use that.
	 * // for those setups where the WP_CONTENT_DIR is not in the default location, we hardcode the path.
	 *
	 * @return string
	 */
	public function get_wp_config_rule() {
		if ( $this->use_dynamic_path ) {
			$rule  = 'if (!defined("RSSSL_HEADERS_ACTIVE") && file_exists( ABSPATH . "wp-content/advanced-headers.php")) {' . "\n";
			$rule .= "\t" . 'require_once ABSPATH . "wp-content/advanced-headers.php";' . "\n" . '}';
		} else {
			$rule  = 'if (!defined("RSSSL_HEADERS_ACTIVE") && file_exists(\'' . WP_CONTENT_DIR . '/advanced-headers.php\')) {' . "\n";
			$rule .= "\t" . 'require_once \'' . WP_CONTENT_DIR . '/advanced-headers.php\';' . "\n" . '}';
		}
		return $rule;
	}

	/**
	 * Check if the wp-config contains the if constant condition, to prevent duplicate loading. If not, try upgrading. If that fails, skip.
	 * Wrapper function added for clearer purpose in code
	 *
	 * @return bool
	 */
	private function wp_config_contains_latest(): bool {
		return $this->update_wp_config_rule();
	}

	/**
	 * Called in upgrade.php, to upgrade older rules to the latest.
	 * Returns true if the wpconfig contains the upgraded lines
	 *
	 * @return bool
	 */
	public function update_wp_config_rule(): bool {
		$file = $this->wpconfig_path();
		if ( ! $file ) {
			return false;
		}

		$content = $this->get_contents( $file );
		$find    = '(file_exists( ABSPATH . "wp-content/advanced-headers.php"))';
		if ( false !== strpos( $content, $find ) ) {
			if ( ! $this->is_writable( $file ) ) {
				return false;
			}
			$replace = '(!defined("RSSSL_HEADERS_ACTIVE") && file_exists( ABSPATH . "wp-content/advanced-headers.php"))';
			$content = str_replace( $find, $replace, $content );
			$this->put_contents( $file, $content );
		}
		return true;
	}

	/**
	 * Admin is not always loaded here, so we define our own function
	 *
	 * @return string|null
	 */
	public function wpconfig_path() {
		// limit nr of iterations to 5.
		$i             = 0;
		$maxiterations = 5;
		$dir           = ABSPATH;
		do {
			++$i;
			if ( $this->file_exists( $dir . '/wp-config.php' ) ) {
				return $dir . '/wp-config.php';
			}
		} while ( ( $dir = realpath( "$dir/.." ) ) && ( $i < $maxiterations ) );//phpcs:ignore
		return null;
	}

	/**
	 * Clear the headers
	 *
	 * @return void
	 */
	public function remove_advanced_headers() {
		$this->uninstall();
	}
}
