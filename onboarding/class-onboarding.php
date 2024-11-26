<?php
defined('ABSPATH') or die();
require_once(rsssl_path . 'class-installer.php');

require_once rsssl_path . 'lib/admin/class-encryption.php';
use RSSSL\lib\admin\Encryption;

class rsssl_onboarding {

	use Encryption;

	private static $_this;
	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}

		self::$_this = $this;
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_settings_page'), 40);
		add_filter("rsssl_run_test", array($this, 'handle_onboarding_request'), 10, 3);
		add_filter("rsssl_do_action", array($this, 'handle_onboarding_action'), 10, 3);

	}

	static function this() {
		return self::$_this;
	}

	public function handle_onboarding_request($response, $test, $data){
		if ( ! rsssl_user_can_manage() ) {
			return [];
		}
		switch( $test ){
			case 'activate_ssl':
				$data['is_rest_request'] = true;
				$response = RSSSL()->admin->activate_ssl($data);
				break;
			case 'activate_ssl_networkwide':
				$response = RSSSL()->multisite->process_ssl_activation_step();
				break;
			default:
				return $response;
		}

		return $response;
	}

	/**
	 * @param $response
	 * @param $action
	 * @param $data
	 *
	 * @return array|bool[]|false|mixed
	 */
	public function handle_onboarding_action($response, $action, $data){
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}
		$error = false;
		$next_action = 'none';
		switch( $action ){
			case 'onboarding_data':
				$response = $this->onboarding_data($data);
				break;
			case 'get_modal_status':
				$response =  ["dismissed" => !$this->show_onboarding_modal()];
				break;
			case 'dismiss_modal':
				$this->dismiss_modal($data);
				break;
			case 'override_ssl_detection':
				$response = $this->override_ssl_detection($data);
				break;
			case 'install_plugin':
				require_once(rsssl_path . 'class-installer.php');
				$plugin = new rsssl_installer(sanitize_title($data['id']));
				$success = $plugin->download_plugin();
				$response = [
					'next_action' => 'activate',
					'success' => $success
				];
				break;
			case 'activate':
				require_once(rsssl_path . 'class-installer.php');
				$plugin = new rsssl_installer(sanitize_title($data['id']));
				$success = $plugin->activate_plugin();
				$response = [
					'next_action' => 'completed',
					'success' => $success
				];
				break;
			case 'update_email':
				$email = sanitize_email($data['email']);
				if  (is_email($email )) {
					rsssl_update_option('notifications_email_address', $email );
					rsssl_update_option('send_notifications_email', 1 );
					if ( $data['includeTips'] ) {
						$this->signup_for_mailinglist( $email );
					}
					$mailer = new rsssl_mailer();
					$mailer->send_verification_mail( $email );
				}

				$response = [
					'success' => true,
				];
				break;
			case 'activate_setting':
				$id = isset($data['id']) ? sanitize_title($data['id']) : false;
				if ($id==='hardening') {
					$recommended_ids = $this->get_hardening_fields();
					foreach ($recommended_ids as $h ){
						rsssl_update_option($h, 1);
					}
				}
				if ($id === 'vulnerability_detection') {
					rsssl_update_option('enable_vulnerability_scanner', 1);

				}
                if ($id === 'two_fa_enabled_roles_totp') {
                    rsssl_update_option('two_fa_enabled_roles_totp', ['administrator']);
                }
				$response = [
					'next_action' => 'completed',
					'success' => true,
				];
				break;

		}
		$response['request_success'] = true;
		return $response;
	}

	/**
	 * Signup for Tips & Tricks from Really Simple Security
	 *
	 * @param string $email
	 *
	 * @return void
	 */
	public function signup_for_mailinglist( string $email): void {
		$license_key = '';
		if ( defined('rsssl_pro') ) {
			$license_key = RSSSL()->licensing->license_key();
			$license_key = $this->decrypt_if_prefixed( $license_key , 'really_simple_ssl_');
		}

		$api_params = array(
			'has_premium' => defined('rsssl_pro'),
			'license' => $license_key,
			'email' => sanitize_email($email),
			'domain' => esc_url_raw( site_url() ),
		);
		wp_remote_post( 'https://mailinglist.really-simple-ssl.com', array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );
	}

	/**
	 * Two possibilities:
	 * - a new install: show activation notice, and process onboarding
	 * - an upgrade to 6. Only show new features.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */

	public function onboarding_data( $data ): array {
		// "warning", // yellow dot
		// "error", // red dot
		// "active" // green dot
		$refresh = isset( $data['forceRefresh'] ) && $data['forceRefresh'] === true;
		$nonce   = $data['nonce'] ?? false;
		if ( ! wp_verify_nonce( $nonce, 'rsssl_nonce' ) ) {
			return [];
		}

		$steps = [
			[
				"id"       => 'activate_ssl',
				"title"    => __( "Welcome to Really Simple Security", 'really-simple-ssl' ),
				"subtitle" => __( "The onboarding wizard will help to configure essential security features in 1 minute! Select your hosting provider to start.", "really-simple-ssl" ),
				"items"    => $this->activate_ssl(),
			],
			[
				"id"       => 'email',
				"title"    => __( "Verify your email", 'really-simple-ssl' ),
				"subtitle" => __( "Really Simple Security will send email notifications and security warnings from your server. We will send a test email to confirm that email is correctly configured on your site. Look for the confirmation button in the email.", "really-simple-ssl" ),
				"button"   => __( "Save and continue", "really-simple-ssl" ),
			],
			[
				"id"       => 'features',
				"title"    => __( "Essential security", 'really-simple-ssl' ),
				"subtitle" => $this->features_subtitle(),
				"items"    => $this->recommended_features(),
				"button"   => __( "Enable", "really-simple-ssl" ),
			],
			[
				"id"       => 'activate_license',
				"title"    => __( "Activate your license key", 'really-simple-ssl' ),
				"subtitle" => '',
				"items"    => [
					'type' => 'license',
				],
				"button"   => __( "Activate", "really-simple-ssl" ),
				"value"    => '',
			],
			[
				"id"       => 'plugins',
				"title"    => __( "We think you will like this", "really-simple-ssl" ),
				"subtitle" => __( "Really Simple Plugins is also the author of the below privacy-focused plugins, including consent management, legal documents and analytics!", "really-simple-ssl" ),
				"items"    => $this->plugins(),
				"button"   => __( "Install", "really-simple-ssl" ),
			],
			[
				"id"       => 'pro',
				"title"    => "Really Simple Security Pro",
				"subtitle" => __( "Heavyweight security features, in a lightweight performant plugin from Really Simple Plugins. Get started with below features and get the latest and greatest updates for peace of mind!", "really-simple-ssl" ),
				"items"    => $this->pro_features(),
				"button"   => __( "Install", "really-simple-ssl" ),
			],
		];

		// Only add activate_license field when rsssl_pro is defined
		if ( ! defined( 'rsssl_pro' ) ) {
			$steps = array_filter( $steps, static function ( $step ) {
				return ! in_array( $step['id'], [ 'activate_license' ] );
			} );
		} else if ( get_option( "rsssl_upgraded_from_free" ) ) {
			$steps = array_filter( $steps, static function ( $step ) {
				return ! in_array( $step['id'], [ 'activate_ssl', 'features', 'email', 'plugins' ] );
			} );

		}

		// Re-order keys to prevent issues after array_filter
		$steps = array_values( $steps );

		//if the user called with a refresh action, clear the cache
		if ( $refresh ) {
			delete_transient( 'rsssl_certinfo' );
		}

		$data_to_return = [
			"request_success"           => true,
			"steps"                     => $steps,
			"ssl_enabled"               => rsssl_get_option( "ssl_enabled" ),
			"ssl_detection_overridden"  => get_option( 'rsssl_ssl_detection_overridden' ),
			'certificate_valid'         => RSSSL()->certificate->is_valid(),
			"networkwide"               => is_multisite() && rsssl_is_networkwide_active(),
			"network_activation_status" => get_site_option( 'rsssl_network_activation_status' ),
			'rsssl_upgraded_from_free'  => get_option( "rsssl_upgraded_from_free" ),
		];


		if ( get_option('rsssl_upgraded_from_free' ) ) {
			delete_option('rsssl_upgraded_from_free' );
		}

		return $data_to_return;

	}

	/**
	 * Return onboarding items for fresh installs
	 * @return array[]
	 */
	function activate_ssl (): array
    {
		$items = [];

		//if the site url is not yet https, the user may need to login again
		if ( strpos( site_url(), 'https://') === false ) {
			$items[] = [
				"title" => __("You may need to login in again, have your credentials prepared.", "really-simple-ssl"),
				"status" => "inactive",
				"id" => "login",
			];
		}

		if ( RSSSL()->certificate->is_valid() ) {
			$items[] = [
				"title" => __("An SSL certificate has been detected", "really-simple-ssl"),
				"status" => "success",
				"id" => "certificate",
			];
		} else if ( RSSSL()->certificate->detection_failed() ) {
			$items[] = [
				"title" => __("Could not test certificate", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
				"status" => "error",
				"id" => "certificate",
			];
		} else {
			$items[] = [
				"title" => __("No SSL certificate has been detected.", "really-simple-ssl") . " " . __("Please refresh the SSL status if a certificate has been installed recently.", "really-simple-ssl"),
				"status" => "error",
				"id" => "certificate",
			];
		}

		return $items;
	}

	public function plugins(): array {
		$items = [];
		$plugins_to_install = [
			[
				"slug" => "burst-statistics",
				'constant_premium' => 'burst_pro',
				"title" => "Burst Statistics",
				"description" => __("Privacy-friendly analytics tool.", "really-simple-ssl"),
			],
			[
				"slug" => "complianz-gdpr",
				'constant_premium' => 'cmplz_premium',
				"title" => "Complianz",
				"description" => __("Consent Management as it should be.", "really-simple-ssl"),
			],
			[
				"slug" => "complianz-terms-conditions",
				'constant_premium' => false,
				"title" => "Complianz Terms & Conditions",
				"description" => __("Terms & Conditions", "really-simple-ssl"),
			]
		];
		foreach ($plugins_to_install as $plugin_info) {
			require_once(rsssl_path . 'class-installer.php');
			$plugin = new rsssl_installer($plugin_info["slug"]);
			$premium_active = $plugin_info['constant_premium'] && defined($plugin_info['constant_premium']);
			$free_active = $plugin->plugin_is_downloaded() && $plugin->plugin_is_activated();

			if( $premium_active || $free_active ) {
				$action = "none";
			} else if( !$plugin->plugin_is_downloaded() ){
				$action = "install_plugin";
			} else if ( $plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated() ) {
				$action = "activate";
			} else {
				$action = "none";
			}

			$items[] = [
				"id" => $plugin_info['slug'],
				"title" => $plugin_info["title"],
				"description" => $plugin_info["description"],
				"action" => $action,
				"activated" => true,
				"current_action" => "none",
			];
		}
		return $items;
	}

	/**
	 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
	 * @return array
	 */
	public function recommended_features(): array
    {
		$features = [
			[
				"title"     => __( "Vulnerability scan", "really-simple-ssl" ),
				"id"        => "vulnerability_detection",
				"options"   => [ "enable_vulnerability_scanner" ],
				"activated" => true,
			],
			[
				"title"     => __( "Essential WordPress hardening", "really-simple-ssl" ),
				"id"        => "hardening",
				"options"   => $this->get_hardening_fields(),
				"activated" => true,
			],
			[
				"title"     => __( "E-mail login", "really-simple-ssl" ),
				"id"        => "two_fa",
				"options"   => [ "login_protection_enabled" ],
				"activated" => true,
			],
			[
				"title"     => __( "Mixed Content Fixer", "really-simple-ssl" ),
				"id"        => "mixed_content_fixer",
				"options"   => [ "mixed_content_fixer" ],
				"activated" => true,
			],
		];

		if ( ! defined( 'rsssl_pro' ) ) {
			$features += [
				[
					"title"     => __( "Firewall", "really-simple-ssl" ),
					"id"        => "firewall",
					"premium"   => true,
					"options"   => [ "enable_firewall" ],
					"activated" => true,
				],
				[
					"title"     => __( "Two-Factor Authentication", "really-simple-ssl" ),
					"id"        => "two_fa",
					"premium"   => true,
					"options"   => [ 'login_protection_enabled'],
					"activated" => true,
				],
				[
					"title"     => __( "Limit Login Attempts", "really-simple-ssl" ),
					"id"        => "limit_login_attempts",
					"premium"   => true,
					"options"   => [ 'enable_limited_login_attempts' ],
					"activated" => true,
				],
				[
					"title"     => __( "Security Headers", "really-simple-ssl" ),
					"id"        => "advanced_headers",
					"premium"   => true,
					"options"   => [],
					"activated" => true,
				],
			];
		}

		return $features;
	}

	/**
	 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
	 * @return array
	 */
	public function pro_features (): array
    {
		return [
			[
				"title" => __("Firewall", "really-simple-ssl"),
				"id" => "firewall",
				"premium" => true,
				"options" => ['enable_firewall'],
				"activated" => true,
			],
			[
				"title" => __("Two-Factor Authentication", "really-simple-ssl"),
				"id" => "two_fa",
				"premium" => true,
				"options" => ['two_fa_enabled_roles_totp'],
                "value" => ['administrator'],
                "activated" => true,
			],
			[
				"title" => __("Limit Login Attempts", "really-simple-ssl"),
				"id" => "limit_login_attempts",
				"premium" => true,
				"options" => ['enable_limited_login_attempts'],
				"activated" => true,
			],
			[
				"title" => __("Security Headers", "really-simple-ssl"),
				"id" => "advanced_headers",
				"premium" => true,
				"options" => [  'upgrade_insecure_requests',
					'x_content_type_options',
					'hsts',
					['x_xss_protection' => 'zero'],
					'x_content_type_options',
					['x_frame_options' => 'SAMEORIGIN'],
					['referrer_policy' => 'strict-origin-when-cross-origin'],
					['csp_frame_ancestors' => 'self'],
				],
				"activated" => true,
			],
			[
				"title" => __("Vulnerability Measures", "really-simple-ssl"),
				"id" => "vulnerability_measures",
				"options" => ["enable_vulnerability_scanner", "measures_enabled"],
				"activated" => true,
			],
			[
				"title" => __("Advanced WordPress Hardening", "really-simple-ssl"),
				"id" => "advanced_hardening",
				"premium" => true,
				"options" => [ 'change_debug_log_location', 'disable_http_methods' ],
				"activated" => true,
			],
//			[
//				"title" => __("File Change Detection", "really-simple-ssl"),
//				"id" => "file_change_detection",
//				"options" => ['file_change_detection'],
//				"activated" => true,
//			],
			[
				"title" => __("Strong Password policy", "really-simple-ssl"),
				"id" => "password_security",
				"options" => ['enforce_password_security_enabled', 'enable_hibp_check'],
				"activated" => true,
			],
		];
	}

	/**
	 * Toggle modal status
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function dismiss_modal($data): void
    {
		if (!rsssl_user_can_manage()) return;
		$dismiss =  $data['dismiss'] ?? false;
		update_option("rsssl_onboarding_dismissed", (bool) $dismiss, false);
	}

	public function maybe_redirect_to_settings_page(): void
    {
		if ( get_transient('rsssl_redirect_to_settings_page' ) ) {
			delete_transient('rsssl_redirect_to_settings_page' );
			if ( !RSSSL()->admin->is_settings_page() ) {
				wp_redirect( add_query_arg(array('page' => 'really-simple-security'), rsssl_admin_url() ) );
				exit;
			}
		}
	}

	/**
	 * Check if any of the recommended features has been disabled
	 * @return bool
	 */
	public function all_recommended_hardening_features_enabled(){
		$recommended_ids = $this->get_hardening_fields();
		foreach ($recommended_ids as $h ){
			if ( rsssl_get_option($h)!=1 ) {
				return false;
			}
		}
		return true;
	}

	private function get_hardening_fields(): array {
		$fields = rsssl_fields(false);
		//get all fields that are recommended
		$recommended = array_filter($fields, function($field){
			return isset($field['recommended']) && $field['recommended'];
		});
		//get all id's from this array
		return array_map( static function($field){
			return $field['id'];
		}, $recommended);
	}

	public function onboarding_rest_route() {
		register_rest_route( 'reallysimplessl/v1', 'onboarding', array(
			'methods'  => 'GET',
			'callback' => array($this, 'onboarding_data'),
			'permission_callback' => function () {
				return rsssl_user_can_manage();
			}
		) );
	}


	/**
	 * Update SSL detection overridden option
	 */

	public function override_ssl_detection($data) {
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}
		$override_ssl = isset($data['overrideSSL']) ? $data['overrideSSL']===true : false;
		if ($override_ssl) {
			update_option('rsssl_ssl_detection_overridden', true, false );
		} else {
			delete_option('rsssl_ssl_detection_overridden' );
		}
		return ['success'=>true];
	}

	/**
	 * Logic if the activation notice should be shown
	 */

	public function show_onboarding_modal(): bool
    {
		if ( get_option("rsssl_onboarding_dismissed") ) {
			return false;
		}

		//ensure the checks have been run
		if ( !RSSSL()->admin->configuration_loaded ) {
			RSSSL()->admin->detect_configuration();
		}

		if ( RSSSL()->admin->do_wpconfig_loadbalancer_fix() && !RSSSL()->admin->wpconfig_has_fixes() ) {
			return false;
		}

		//for multisite environments, we check if the activation process was started but not completed.
		if ( is_multisite() && RSSSL()->multisite->ssl_activation_started_but_not_completed() ){
			return true;
		}

		$is_upgrade = get_option('rsssl_show_onboarding');
		if ( rsssl_get_option('ssl_enabled') && !$is_upgrade ) {
			return false;
		}

		if ( defined( "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE" ) && RSSSL_DISMISS_ACTIVATE_SSL_NOTICE ) {
			return false;
		}

		//don't show in our Let's Encrypt wizard
		if ( isset( $_GET['letsencrypt'] ) ) {
			return false;
		}

		if ( ! RSSSL()->admin->wpconfig_ok() ) {
			return false;
		}

		if ( ! rsssl_user_can_manage() ) {
			return false;
		}

		return true;
	}

	/**
	 * @return void
	 *
	 * Maybe reset onboarding modal
	 */
	public function reset_onboarding(): void
    {
		//ensure onboarding triggers again so user gets to enter the license on reload.
		update_option( "rsssl_show_onboarding", true, false );
		update_option( "rsssl_onboarding_dismissed", false, false );
		update_option( "rsssl_upgraded_from_free", true, false );
	}

	/**
	 * @return string|null
	 *
	 * Generate notice based on Pro being installed or not
	 */
	public function features_subtitle(): ?string
    {
		$notice = __( "Instantly configure these essential features.", "really-simple-ssl" );

		if ( ! defined('rsssl_pro') ) {
			$notice .= ' ' . sprintf(
					__( "Please %sconsider upgrading to Pro%s to enjoy all simple and performant security features.", "really-simple-ssl" ),
					'<a href="https://really-simple-ssl.com/pro?mtm_campaign=security&mtm_source=free&mtm_content=upgrade" target="_blank">',
					'</a>'
				);
		}

		return $notice;
	}

}