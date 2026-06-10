<?php
defined( 'ABSPATH' ) || die();

use RSSSL\Security\RSSSL_Htaccess_File_Manager;
use RSSSL\Pro\Security\WordPress\Rsssl_Geo_Block;

/**
 * Class to handle the creation and include of the firewall
 */
class rsssl_firewall_manager {
	/**
	 * Marker string for .htaccess rules related to auto prepend file.
	 */
	private const HTACCESS_MARKER_PREPEND = 'Really Simple Auto Prepend File';
	/**
	 * Firewall object
	 */
	private static rsssl_firewall_manager $this;

	/**
	 * The htaccess manager
	 */
	public RSSSL_Htaccess_File_Manager $htaccessManager;
	/**
	 * File
	 *
	 * @var string
	 */
	private $file;
	/**
	 * If we can use a dynamic path
	 *
	 * @var bool
	 */
	private $dynamic_path;
	/**
	 * Path to the firewall.php file, filterable.
	 *
	 * @var string
	 */
	private string $firewall_file_path;
	/**
	 * Rules to add to the firewall.
	 *
	 * @var string
	 */
	private $rules = '';
	/**
	 * The WP_Filesystem instance, used for file operations.
	 *
	 */
	private $wp_filesystem;

	public function __construct(RSSSL_Htaccess_File_Manager $htaccessManager) {

		if ( isset( self::$this ) ) {
			wp_die();
		}
		self::$this = $this;

		$this->htaccessManager = $htaccessManager;

		// Set dynamic path detection dynamically to handle environment changes
		$this->dynamic_path = $this->get_dynamic_path();

		// Determine firewall.php path, allowing custom content dir or fallback.
		if ( $this->dynamic_path ) {
			$wpContentPath = ABSPATH . 'wp-content/';
		} else {
			$wpContentPath = WP_CONTENT_DIR . '/';
		}

		$this->firewall_file_path = apply_filters(
			'rsssl_firewall_file_path',
			$wpContentPath . 'firewall.php'
		);

		// Set the file path dynamically so we can detect WP_CONTENT_DIR changes
		$this->file = $this->get_advanced_headers_path();

		// Keep firewall generation aligned with the shared rule rebuild lifecycle.
		add_action( 'rsssl_update_advanced_headers', array( $this, 'update_advanced_headers' ), 10 );
		add_action( 'rsssl_after_saved_fields', array( $this, 'install' ), 100 );
		add_action( 'rsssl_deactivate', array( $this, 'uninstall' ), 20 );
		add_filter( 'rsssl_htaccess_security_rules', array( $this, 'add_prepend_file_htaccess_rule' ), 5 );

		// Proactively check for environment changes on admin loads
		add_action( 'admin_init', array( $this, 'maybe_regenerate_firewall' ), 5 );

		add_filter( 'rsssl_notices', array( $this, 'notices' ) );

		// WP Rocket changes whether auto-prepend should stay in the shared root rule set.
		add_action( 'rocket_activation', array( $this, 'queue_prepend_file_htaccess_removal' ) );
		add_action( 'rocket_deactivation', array( $this, 'queue_prepend_file_htaccess_sync' ) );

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
	public function install(): void {
		$this->sync_advanced_headers_state( true );
	}

	/**
	 * Refresh the advanced-headers / firewall files without directly queueing root `.htaccess` changes.
	 *
	 * This is used for advanced-headers-only updates such as CSP learning-mode writes where the file contents
	 * need to change but the shared `.htaccess` flow should not be touched.
	 */
	public function update_advanced_headers(): void {
		$this->sync_advanced_headers_state( false );
	}

	/**
	 * Synchronize the advanced-headers bootstrap files and, when applicable, request a shared root `.htaccess` update.
	 */
	private function sync_advanced_headers_state( bool $maybe_queue_root_htaccess_update ): void {
		// Don't regenerate files during deactivation
		if ( doing_action( 'rsssl_deactivate' ) ) {
			return;
		}

		if ( ! rsssl_admin_logged_in() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		$this->rules = apply_filters( 'rsssl_firewall_rules', '' );
		$has_rules = ! empty( trim( $this->rules ) );

		// `true` means "keep the root prepend state aligned when applicable": queue removal when no rules remain,
		// otherwise only queue a sync for an existing root `.htaccess` target outside `plugins_loaded`.
		if ( ! $has_rules ) {
			if ( $maybe_queue_root_htaccess_update ) {
				$this->queue_prepend_file_htaccess_removal();
			}

			$this->remove_prepend_file_in_wp_config();
			$this->remove_auto_prepend_file_in_user_ini();
			return;
		}

		$this->update_firewall( $this->rules );

		$this->include_prepend_file_in_wp_config();
		$should_queue_root_htaccess_sync = $maybe_queue_root_htaccess_update
			&& $this->has_root_htaccess_file()
			&& current_filter() !== 'plugins_loaded';
		if ( $should_queue_root_htaccess_sync ) {
			// Avoid queueing the shared root sync during `plugins_loaded`
			// later admin-side flows can request it when applicable.
			$this->queue_prepend_file_htaccess_sync();
		}

		if ( $this->has_user_ini_file() ) {
			$this->include_prepend_file_in_user_ini();
		}
	}

	/**
	 * Remove file and file inclusions
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		$this->remove_prepend_file_in_wp_config();
		$this->remove_auto_prepend_file_in_user_ini();

		$this->empty_file();
		$this->delete_test_file();

		// Delete firewall.php file using the existing handler
		if ( class_exists( '\RSSSL\Pro\Security\WordPress\Firewall\Rsssl_Firewall_File_Handler' ) ) {
			$firewall_handler = new \RSSSL\Pro\Security\WordPress\Firewall\Rsssl_Firewall_File_Handler();
			$firewall_handler->delete();
		}
	}

	/**
	 * Proactively check for environment changes on admin loads
	 * This ensures firewall regeneration after site clones/migrations
	 *
	 * @return void
	 */
	public function maybe_regenerate_firewall(): void {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		// Only check if we have firewall rules that need to be active
		if ( ! $this->has_rules() ) {
			return;
		}

		// Only run the check if environment has changed
		if ( $this->should_regenerate_firewall() ) {
			// Trigger the full installation process for firewall.php
			$this->install();
			// Also generate the Geo Block firewall settings
			$fireWallSettingIsEnabled = rsssl_get_option( 'enable_firewall', false );
			if ( $fireWallSettingIsEnabled ) {
				$geoBlock = Rsssl_Geo_Block::get_instance();
				$geoBlock->generate_firewall_rules();
			}
		}
	}

	/**
	 * Check if our firewall file exists
	 *
	 * @param string $file // filename, including path
	 *
	 * @return bool
	 */
	private function file_exists( string $file ): bool {
		$wp_filesystem = $this->get_file_system();

		// Use WP Filesystem if available, otherwise fall back to direct operations
		return $wp_filesystem ? $wp_filesystem->is_file( $file ) : file_exists( $file );
	}

	/**
	 * Get the WP_Filesystem instance with lazy loading
	 *
	 * @return false|WP_Filesystem_Base
	 */
	private function get_file_system() {
		// Return cached instance if available
		if ( $this->wp_filesystem !== null ) {
			return $this->wp_filesystem;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( false === ( $creds = request_filesystem_credentials( site_url(), '', false, false, null ) ) ) {
			$this->wp_filesystem = false;
			return false; // stop processing here.
		}
		global $wp_filesystem;
		if ( ! WP_Filesystem( $creds ) ) {
			// request_filesystem_credentials(site_url(), '', true, false, null);//phpcs:ingore
			$this->wp_filesystem = false;
			return false;
		}

		// Cache the instance
		$this->wp_filesystem = $wp_filesystem;
		return $wp_filesystem;
	}

	/**
	 * Update the file that contains the firewall rules, advanced-headers.php
	 *
	 * @param string $rules //rules to add to the firewall.
	 *
	 * @return void
	 */
	public function update_firewall( string $rules ): void
	{
		if ( ! rsssl_admin_logged_in() ) {
			return;
		}

		$contents  = '<?php' . "\n";
		$contents .= '/**' . "\n";
		$contents .= '* This file is created by Really Simple Security' . "\n";
		$contents .= '*/' . "\n\n";
		$contents .= 'if (defined("SHORTINIT") && SHORTINIT) return;' . "\n\n";
		$contents .= '$base_path = dirname(__FILE__);' . "\n";
		$contents .= 'if( file_exists( $base_path . "/rsssl-safe-mode.lock" ) ) {' . "\n";
		$contents .= '    if ( ! defined( "RSSSL_SAFE_MODE" ) ) {' . "\n";
		$contents .= '        define( "RSSSL_SAFE_MODE", true );' . "\n";
		$contents .= '    }' . "\n";
		$contents .= '    return;' . "\n";
		$contents .= '}' . "\n\n";
		// allow disabling of headers for detection purposes.
		$contents .= 'if ( isset($_GET["rsssl_header_test"]) && (int) $_GET["rsssl_header_test"] ===  ' . $this->get_headers_nonce() . ' ) return;' . "\n\n";
		//if already included at some point, don't execute again.
		$contents .= 'if ( defined("RSSSL_HEADERS_ACTIVE" ) ) return;' . "\n";
		$contents .= 'define( "RSSSL_HEADERS_ACTIVE", true );' . "\n";

		// If the main firewall (firewall.php) is enabled, add the include directive for it.
		if ( rsssl_get_option( 'enable_firewall', false ) ) {
			$firewallFilePath = $this->firewall_file_path;
			$contents        .= 'if ( file_exists( "' . $firewallFilePath . '" ) ) {' . "\n";
			$contents        .= '    require_once "' . $firewallFilePath . '";' . "\n";
			$contents        .= '}' . "\n\n";
		}

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
		if ( ! rsssl_admin_logged_in() ) {
			return;
		}

		// Check if file is writable (or doesn't exist yet, which is fine)
		if ( $this->file_exists( $file ) && ! $this->is_writable( $file ) ) {
			return;
		}

		// Check if directory is writable before attempting to create new file
		$directory = dirname( $file );
		if ( ! $this->file_exists( $file ) && ! $this->is_writable( $directory ) ) {
			return;
		}

		$wp_filesystem = $this->get_file_system();

		if ( $wp_filesystem === false ) {
			// Double-check directory writability before fallback to prevent PHP warnings
			if ( ! is_writable( $directory ) ) {
				return;
			}
			file_put_contents( $file, $contents );//phpcs:ignore
			return;
		}

		$wp_filesystem->put_contents( $file, $contents );

		// Only chmod files other than .htaccess and wp-config.php
		if ( strpos($file, 'htaccess') === false && strpos($file, 'wp-config.php') === false ) {
			$wp_filesystem->chmod( $file, 0644 );
		}
	}

	/**
	 * Get the contents of a file
	 *
	 * @param string $file //filename, including path.
	 *
	 * @return string
	 */
	private function get_contents( string $file ): string {
		// Validate that file path is not empty
		if ( empty( $file ) ) {
			return '';
		}

		$wp_filesystem = $this->get_file_system();

		if ( $wp_filesystem === false ) {
			return file_exists( $file ) ? file_get_contents( $file ) : '';//phpcs:ignore
		}

		$result = $wp_filesystem->get_contents( $file );
		return $result ? $result : '';
	}

	/**
	 * Empty the advanced-headers.php file instead of deleting it.
	 * This prevents 500 errors when user.ini is cached and still references this file.
	 *
	 * @return void
	 */
	private function empty_file(): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$contents = <<<PHP
<?php
// This file was created by Really Simple Security
// It is no longer used and safe to delete
PHP;

		$this->put_contents( $this->file, $contents );
	}

	/**
	 * Get the path to the advanced-headers-test.php file.
	 *
	 * @return string
	 */
	private function get_test_file_path(): string {
		return WP_CONTENT_DIR . '/advanced-headers-test.php';
	}

	/**
	 * Delete the advanced-headers-test.php file if it exists.
	 *
	 * @return void
	 */
	private function delete_test_file(): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$test_file = $this->get_test_file_path();
		if ( ! file_exists( $test_file ) ) {
			return;
		}

		$wp_filesystem = $this->get_file_system();
		if ( $wp_filesystem === false ) {
			unlink( $test_file );//phpcs:ignore
			return;
		}

		$wp_filesystem->delete( $test_file );
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
		if ( defined('RSSSL_IS_FLYWHEEL') && RSSSL_IS_FLYWHEEL && isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
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
	private function sanitize_path( $path ): string {
		// prevent path traversal.
		return str_replace( '../', '/', realpath( sanitize_text_field( $path ) ) );
	}

	/**
	 * Check whether the shared root `.htaccess` target already exists.
	 * The prepend rule can only be managed through the centralized root writer when there is a real file to update.
	 *
	 * @return bool
	 */
	private function has_root_htaccess_file(): bool {
		return $this->file_exists( $this->htaccessManager->get_root_htaccess_target_path() );
	}

	/**
	 * Add the prepend rule to the centralized root .htaccess rule batch.
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function add_prepend_file_htaccess_rule( array $rules ): array
	{
		$rule = $this->get_root_htaccess_prepend_rule_definition();
		if ( empty( $rule ) ) {
			return $rules;
		}

		$rules[] = $rule;
		return $rules;
	}

	/**
	 * Return the root-rule definition for the auto-prepend include when the file/config state is ready.
	 */
	private function get_root_htaccess_prepend_rule_definition(): array {
		if ( $this->htaccessManager->determineExistingRootHtaccessFilePath() === '' ) {
			return [];
		}

		if ( ! $this->file_exists( $this->file ) ) {
			return [];
		}

		if ( ! $this->wp_config_contains_latest() ) {
			return [];
		}

		return [
			'identifier' => self::HTACCESS_MARKER_PREPEND,
			'rules'      => $this->build_prepend_file_htaccess_rules(),
		];
	}

	/**
	 * Queue the centralized `.htaccess` writer so prepend inclusion follows the shared commit path.
	 *
	 * @return void
	 */
	public function queue_prepend_file_htaccess_sync(): void
	{
		if ( ! function_exists( 'rsssl_request_managed_htaccess_rebuild' ) ) {
			return;
		}

		rsssl_request_managed_htaccess_rebuild();
	}

	/**
	 * Build the literal auto-prepend `.htaccess` lines, including any matching `user.ini` protection block.
	 */
	private function build_prepend_file_htaccess_rules() : string
	{
		if ( defined('RSSSL_HTACCESS_SKIP_AUTO_PREPEND') && RSSSL_HTACCESS_SKIP_AUTO_PREPEND ) {
			return '';
		}
		if (isset(RSSSL()->server) ) {
			$config = RSSSL()->server->auto_prepend_config();
		} else {
			$config = get_option('rsssl_auto_prepend_config');
			if (empty($config)) {
				return '';
			}
		}
		$file = addcslashes($this->file, "'");
		switch ($config) {
			case 'litespeed':
				$rules = array(
					'<IfModule LiteSpeed>',
					'php_value auto_prepend_file ' . $file ,
					'</IfModule>',
					'<IfModule lsapi_module>',
					'php_value auto_prepend_file ' . $file,
					'</IfModule>',
				);
				break;
			case 'apache-mod_php':
			default:
				$rules = array(
					'<IfModule mod_php7.c>',
					'php_value auto_prepend_file ' . $file ,
					'</IfModule>',
					'<IfModule mod_php.c>',
					'php_value auto_prepend_file ' . $file,
					'</IfModule>',
				);
		}

		$userIni = ini_get('user_ini.filename');
		if ($userIni) {
			array_push(
				$rules,
				sprintf( '<Files "%s">',
					addcslashes( $userIni, '"' ) ),
				'<IfModule mod_authz_core.c>',
				'Require all denied', '</IfModule>',
				'<IfModule !mod_authz_core.c>',
				'Order deny,allow', 'Deny from all',
				'</IfModule>',
				'</Files>'
			);
		}

		return implode( "\n", $rules );
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
		$file = $this->wpconfig_path();
		if ( empty( $file ) ) {
			update_option( 'rsssl_firewall_error', 'wpconfig-notfound', false );
			return;
		}
		$content = $this->get_contents( $file );
		if ( strpos( $content, 'advanced-headers.php' ) === false ) {
			$rule = $this->get_wp_config_rule();

			// if RSSSL comment is found, insert after.
			$rsssl_comment = '//END Really Simple Security Server variable fix';
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
	 * Queue the centralized `.htaccess` writer so the prepend block is removed through the shared commit path.
	 *
	 * @return void
	 */
	public function queue_prepend_file_htaccess_removal(): void
	{
		if ( ! function_exists( 'rsssl_request_managed_htaccess_rebuild' ) ) {
			return;
		}

		rsssl_request_managed_htaccess_rebuild();
	}

	/**
	 * Remove the prepend file from the config
	 *
	 * @return void
	 */
	private function remove_prepend_file_in_wp_config(): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$file = $this->wpconfig_path();
		if ( empty( $file ) ) {
			return;
		}
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
		$wp_filesystem = $this->get_file_system();

		// Use WP Filesystem if available, otherwise fall back to direct operations
		return $wp_filesystem ? $wp_filesystem->is_writable( $file ) : is_writable( $file );//phpcs:ignore
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
		$this->rules = apply_filters( 'rsssl_firewall_rules', '' );
		return ! empty( trim( $this->rules ) );
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
		if ( $this->dynamic_path ) {
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

		// Allow the wp-config.php path to be overridden via a filter.
		$filtered_path = apply_filters( 'rsssl_wpconfig_path', '' );

		// If a filtered path is provided, validate it.
		if ( ! empty( $filtered_path ) ) {
			$directory = dirname( $filtered_path );

			// Ensure the directory exists before checking for the file.
			if ( is_dir( $directory ) && $this->file_exists( $filtered_path ) ) {
				return $filtered_path;
			}
		}

		// Limit number of iterations to 5.
		$i             = 0;
		$maxiterations = 5;
		$dir           = ABSPATH;
		do {
			++ $i;
			if ( $this->file_exists( $dir . 'wp-config.php' ) ) {
				return $dir . 'wp-config.php';
			}
		} while ( ( $dir = realpath( "$dir/.." ) ) && ( $i < $maxiterations ) );//phpcs:ignore

		return '';
	}

	/**
	 * Clear the headers
	 *
	 * @return void
	 */
	public function remove_advanced_headers() {
		$this->uninstall();
	}

	/**
	 * Check if the firewall file should be regenerated
	 * This detects environment changes like WP Engine clones
	 * Also returns true if the file does not exist yet
	 *
	 * @return bool
	 */
	private function should_regenerate_firewall(): bool {

		if ( ! $this->file_exists( $this->file ) ) {
			return true;
		}

		// Check if we have stored environment signature
		$stored_signature = get_option( 'rsssl_firewall_environment_signature' );
		$current_signature = $this->get_environment_signature();

		// If no stored signature, store it and regenerate
		if ( ! $stored_signature ) {
			update_option( 'rsssl_firewall_environment_signature', $current_signature, false );
			return true;
		}

		// If signature changed, update it and regenerate
		if ( $stored_signature !== $current_signature ) {
			update_option( 'rsssl_firewall_environment_signature', $current_signature, false );
			return true;
		}

		return false;
	}

	/**
	 * Generate a signature of the current environment
	 * Used to detect when the site has been cloned or migrated
	 *
	 * @return string
	 */
	private function get_environment_signature(): string {
		$signature_parts = array(
			WP_CONTENT_DIR,
			ABSPATH,
			get_home_url(),
			get_site_url(),
		);

		return md5( implode( '|', $signature_parts ) );
	}

	/**
	 * Get the advanced headers file path
	 * Always uses WP_CONTENT_DIR which is dynamically set by WordPress
	 *
	 * @return string
	 */
	private function get_advanced_headers_path(): string {
		return WP_CONTENT_DIR . '/advanced-headers.php';
	}

	/**
	 * Check if we can use a dynamic path for the advanced headers file
	 * @return string
	 */
	private function get_dynamic_path(): string {
		return WP_CONTENT_DIR === ABSPATH . 'wp-content';
	}

	/**
	 * Check if a user.ini file exists or is in user.
	 *
	 * @return bool
	 */
	private function has_user_ini_file():bool {
		$userIni = ini_get('user_ini.filename');
		if ( $userIni ) {
			return true;
		}
		return false;
	}

	/**
	 * Add auto prepend file to user.ini
	 *
	 * @return void
	 */
	private function include_prepend_file_in_user_ini():void{
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( defined('RSSSL_HTACCESS_SKIP_AUTO_PREPEND') && RSSSL_HTACCESS_SKIP_AUTO_PREPEND ) {
			return;
		}

		$config = RSSSL()->server->auto_prepend_config();
		if ( !$this->has_user_ini_file() ) {
			return;
		}
		$autoPrependIni = '';
		$userIniPath = $this->get_user_ini_path();
		if ( empty( $userIniPath ) ) {
			return;
		}

		// .user.ini configuration
		switch ($config) {
			case 'cgi':
			case 'nginx':
			case 'apache-suphp':
			case 'litespeed':
			case 'iis':
				$autoPrependIni = sprintf("; BEGIN Really Simple Auto Prepend File
auto_prepend_file = '%s'
; END Really Simple Auto Prepend File", addcslashes($this->file, "'"));
				break;
		}

		if ( empty( $autoPrependIni ) ) {
			return;
		}

		$userIniContent = $this->get_contents( $userIniPath );
		$updatedUserIniContent = $autoPrependIni;
		if ( $userIniContent !== '' ) {
			$updatedUserIniContent = str_replace( 'auto_prepend_file', ';auto_prepend_file', $userIniContent );
			$regex = '/; BEGIN Really Simple Auto Prepend File.*?; END Really Simple Auto Prepend File/is';
			if ( preg_match( $regex, $updatedUserIniContent ) === 1 ) {
				$replacement = preg_replace( $regex, $autoPrependIni, $updatedUserIniContent );
				if ( ! is_string( $replacement ) ) {
					return;
				}
				$updatedUserIniContent = $replacement;
			} else {
				$updatedUserIniContent .= "\n" . $autoPrependIni;
			}
		}

		if ( $updatedUserIniContent === $userIniContent ) {
			return;
		}

		$this->put_contents( $userIniPath, $updatedUserIniContent );
	}

	/**
	 * Get the user.ini path
	 *
	 * @return false|string
	 */
	public function get_user_ini_path() {
		$userIni = ini_get('user_ini.filename');
		if ($userIni) {
			return $this->get_home_path() . $userIni;
		}
		return false;
	}

	/**
	 * Remove the added auto prepend file
	 *
	 * @return void
	 */
	private function remove_auto_prepend_file_in_user_ini() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( ! $this->has_user_ini_file() ) {
			return;
		}

		$userIniPath = $this->get_user_ini_path();
		if ( empty( $userIniPath ) ) {
			return;
		}
		$userIniContent = $this->get_contents( $userIniPath );
		$updatedUserIniContent = preg_replace(
			'/; BEGIN Really Simple Auto Prepend File.*?; END Really Simple Auto Prepend File/is',
			'',
			$userIniContent
		);
		if ( ! is_string( $updatedUserIniContent ) ) {
			return;
		}

		$updatedUserIniContent = str_replace( 'auto_prepend_file', ';auto_prepend_file', $updatedUserIniContent );
		if ( $updatedUserIniContent === $userIniContent ) {
			return;
		}

		$this->put_contents( $userIniPath, $updatedUserIniContent );
	}

}
