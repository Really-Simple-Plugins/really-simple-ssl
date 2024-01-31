<?php
defined('ABSPATH') or die();
require_once(rsssl_path . 'class-installer.php');

class rsssl_onboarding {
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
//		delete_option('rsssl_network_activation_status');
//		delete_option("rsssl_onboarding_dismissed");
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
	 * @param $data
	 * @param $action
	 * @param $request
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
	 * Signup for Tips & Tricks from Really Simple SSL
	 *
	 * @param string $email
	 *
	 * @return void
	 */
	public function signup_for_mailinglist( string $email): void {
		$license_key = '';
		if ( defined('rsssl_pro_version') ) {
			$license_key = RSSSL_PRO()->licensing->license_key();
			$license_key = RSSSL_PRO()->licensing->maybe_decode( $license_key );
		}

		$api_params = array(
			'has_premium' => defined('rsssl_pro_version'),
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
		$refresh = isset($data['forceRefresh']) && $data['forceRefresh']===true;
		$nonce = $data['nonce'] ?? false;
		if ( !wp_verify_nonce($nonce, 'rsssl_nonce') ) {
			return [];
		}

		if( !defined('rsssl_pro_version')) {
			$info = __('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'). " " . sprintf('<a target="_blank" rel="noopener noreferrer" href="%s">%s</a>', RSSSL()->admin->pro_url, __("Check out Really Simple SSL Pro", "really-simple-ssl"));;
		}

		$steps = [
			[
				"id" => 'activate_ssl',
				"title" => __( "Really Simple SSL & Security", 'really-simple-ssl' ),
				"subtitle" => __("We have added many new features to our plugin, now bearing the name Really Simple SSL & Security. But we start like we did almost 10 years ago. Optimising your Encryption with SSL.", "really-simple-ssl"),
				"items" => $this->activate_ssl(),
			],
			[
				"id" => 'features',
				"title" => get_option('rsssl_show_onboarding') ? __( "Thanks for updating!", 'really-simple-ssl' ) : __( "Congratulations!", 'really-simple-ssl' ),
				"subtitle" => __("These are some of our new features, and weʼre just getting started.", "really-simple-ssl")." ".
				              __("A lightweight plugin with heavyweight security features, focusing on performance and usability.", "really-simple-ssl"),
				"items" => $this->recommended_features(),
				"button" => __("Enable", "really-simple-ssl"),
			],
			[
				"id" => 'email',
				"title" => __( "Get notified!", 'really-simple-ssl' ),
				"subtitle" => __("We use email notification to explain important updates in plugin settings.", "really-simple-ssl").' '.__("Add your email address below.", "really-simple-ssl"),
				"button" => __("Save and continue", "really-simple-ssl"),
			],
			[
				"id" => 'plugins',
				"title" => __("Free plugins", "really-simple-ssl"),
				"subtitle" => __("Really Simple Plugins is also the author of the below privacy-focused plugins, including consent management, legal documents and analytics!", "really-simple-ssl"),
				"items" => $this->plugins(),
				"button" => __("Install", "really-simple-ssl"),
			],
			[
				"id" => 'pro',
				"title" => __("Really Simple Security Pro", "really-simple-ssl"),
				"subtitle" => __("Heavyweight security features, in a lightweight performant plugin from Really Simple Plugins. Get started with below features and get the latest and greatest updates for a peace of mind!", "really-simple-ssl"),
				"items" => $this->pro_features(),
				"button" => __("Install", "really-simple-ssl"),
			],
		];

		//if the user called with a refresh action, clear the cache
		if ($refresh) {
			delete_transient('rsssl_certinfo');
		}
		return [
			"request_success" =>true,
			"steps" => $steps,
			"ssl_enabled" => rsssl_get_option("ssl_enabled"),
			"ssl_detection_overridden" => get_option('rsssl_ssl_detection_overridden'),
			'certificate_valid' => RSSSL()->certificate->is_valid(),
			"networkwide" => is_multisite() && rsssl_is_networkwide_active(),
			"network_activation_status" => get_site_option('rsssl_network_activation_status'),
		];
	}

	/**
	 * Return onboarding items for fresh installs
	 * @return array[]
	 */
	function activate_ssl () {
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
				"title" => __("Could not test certificate.", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
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
				"description" => __("Terms & Conditions.", "really-simple-ssl"),
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
	public function recommended_features () {
		return [
			[
				"title" => __("Mixed Content Fixer", "really-simple-ssl"),
				"id" => "mixed_content_fixer",
				"options" => ["mixed_content_fixer"],
				"activated" => true,
			],
			[
				"title" => __("Vulnerability Detection", "really-simple-ssl"),
				"id" => "vulnerability_detection",
				"options" => ["enable_vulnerability_scanner"],
				"activated" => true,
			],
			[
				"title" => __("Recommended Hardening Features", "really-simple-ssl"),
				"id" => "hardening",
				"options" => $this->get_hardening_fields(),
				"activated" => true,
			],
			[
				"title" => __("Run System Health Scan", "really-simple-ssl"),
				"id" => "health_scan",
				"options" => [],
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
				"title" => __("Two Factor Authentication", "really-simple-ssl"),
				"id" => "two_fa",
				"premium" => true,
				"options" => ['login_protection_enabled', 'two_fa_enabled'],
				"activated" => true,
			],
			[
				"title" => __("Advanced Security Headers", "really-simple-ssl"),
				"id" => "advanced_headers",
				"premium" => true,
				"options" => [],
				"activated" => true,
			],
			[
				"title" => __("Advanced Hardening Features", "really-simple-ssl"),
				"id" => "advanced_hardening",
				"premium" => true,
				"options" => [],
				"activated" => true,
			],
		];
	}

	/**
	 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
	 * @return array
	 */
	public function pro_features () {
		return [
			[
				"title" => __("Limit Login Attempts", "really-simple-ssl"),
				"id" => "limit_login_attempts",
				"premium" => true,
				"options" => ['enable_limited_login_attempts'],
				"activated" => true,
			],
			[
				"title" => __("Two Factor Authentication", "really-simple-ssl"),
				"id" => "two_fa",
				"premium" => true,
				"options" => ['login_protection_enabled', 'two_fa_enabled'],
				"activated" => true,
			],
			[
				"title" => __("Advanced Security Headers", "really-simple-ssl"),
				"id" => "advanced_headers",
				"premium" => true,
				"options" => [],
				"activated" => true,
			],
			[
				"title" => __("Password Security", "really-simple-ssl"),
				"id" => "password_security",
				"options" => ['enforce_password_security_enabled'],
				"activated" => true,
			],
			[
				"title" => __("Advanced Hardening", "really-simple-ssl"),
				"id" => "advanced_hardening",
				"premium" => true,
				"options" => [ 'change_debug_log_location', 'disable_http_methods' ],
				"activated" => true,
			],
			[
				"title" => __("Vulnerability Measures", "really-simple-ssl"),
				"id" => "vulnerability_measures",
				"options" => ["vulnerabilities_measures"],
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
	public function dismiss_modal($data){
		if (!rsssl_user_can_manage()) return;
		$dismiss =  $data['dismiss'] ?? false;
		update_option("rsssl_onboarding_dismissed", (bool) $dismiss, false);
	}

	public function maybe_redirect_to_settings_page() {
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

	function show_onboarding_modal() {
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

}
