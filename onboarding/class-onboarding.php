<?php
defined('ABSPATH') or die();
require_once(rsssl_path . 'class-installer.php');

class rsssl_onboarding {
	private static $_this;
	private $hardening = [
		'disable_file_editing',
		'hide_wordpress_version',
		'block_code_execution_uploads',
		'disable_login_feedback',
		'disable_user_enumeration',
		'disable_indexing',
	];

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}

		self::$_this = $this;
		add_action( 'rest_api_init', array($this, 'onboarding_rest_route'), 10 );
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
					if ( $data['sendTestEmail'] ) {
						$mailer = new rsssl_mailer();
						$mailer->send_test_mail();
					}
					if ( $data['includeTips'] ) {
						$this->signup_for_mailinglist( $email );
					}
				}

				$response = [
					'success' => true,
				];
				break;
			case 'activate_setting':
				foreach ($this->hardening as $h ){
					rsssl_update_option($h, true);
				}
				$response = [
					'next_action' => 'none',
					'success' => true,
				];
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

	public function onboarding_data( WP_REST_Request $request): array {
		// "warning", // yellow dot
		// "error", // red dot
		// "active" // green dot
		$info = "";
		$refresh = isset($_GET['forceRefresh']) && $_GET['forceRefresh']===true;
		$nonce = $_GET['nonce'] ?? false;
		if ( !wp_verify_nonce($nonce, 'rsssl_nonce') ) {
			return [];
		}
		if( !defined('rsssl_pro_version')) {
			$info = __('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl'). " " . sprintf('<a target="_blank" href="%s">%s</a>', RSSSL()->admin->pro_url, __("Check out Really Simple SSL Pro", "really-simple-ssl"));;
		}

		$steps = [
			[
				"id" => 'activate_ssl',
				"title" => __( "Almost ready to migrate to SSL!", 'really-simple-ssl' ),
				"subtitle" => __("Before you migrate, please check for:", "really-simple-ssl"),
				"items" => $this->first_step(),
				"info_text" => $info,
			],
			[
				"id" => 'email',
				"title" => __( "Get notified!", 'really-simple-ssl' ),
				"subtitle" => __("We use email notification to explain important updates in plugin settings.", "really-simple-ssl").' '.__("Add your email address below.", "really-simple-ssl"),
			],
			[
				"id" => 'onboarding',
				"title" => get_option('rsssl_show_onboarding') ? __( "Thanks for updating!", 'really-simple-ssl' ) : __( "Congratulations!", 'really-simple-ssl' ),
				"subtitle" => __("Now have a look at our new features.", "really-simple-ssl"),
				"items" => $this->second_step(),
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
	function first_step () {
		$items = [
			[
				"title" => __("Http references in your .css and .js files: change any http:// into https://", "really-simple-ssl"),
				"status" => "inactive",
			],
			[
				"title" => __("Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.", "really-simple-ssl"),
				"status" => "inactive",
			],
			[
				"title" => __("You may need to login in again.", "really-simple-ssl"),
				"status" => "inactive",
			],
		];

		if ( RSSSL()->certificate->is_valid() ) {
			$items[] = [
				"title" => __("An SSL certificate has been detected", "really-simple-ssl"),
				"status" => "success"
			];
		} else if ( RSSSL()->certificate->detection_failed() ) {
			$items[] = [
				"title" => __("Could not test certificate.", "really-simple-ssl") . " " . __("Automatic certificate detection is not possible on your server.", "really-simple-ssl"),
				"status" => "error"
			];
		} else {
			$items[] = [
				"title" => __("No SSL certificate has been detected.", "really-simple-ssl") . " " . __("Please refresh the SSL status if a certificate has been installed recently.", "really-simple-ssl"),
				"status" => "error"
			];
		}

		return $items;
	}

	/**
	 * Returns onboarding items if user upgraded plugin to 6.0 or SSL is detected
	 * @return array
	 */
	public function second_step () {
		$plugins_to_install = [
			[
				"slug" => "burst-statistics",
				'constant_premium' => false,
				"title" => "Burst Statistics",
				"description" => __("Self-hosted, Privacy-friendly analytics tool", "really-simple-ssl"),
				'read_more' => 'https://really-simple-plugins.com',//we only want one button, show we show it with the first plugin, then position it in the middle
			],
			[
				"slug" => "complianz-gdpr",
				'constant_premium' => 'cmplz_premium',
				"title" => "Complianz",
				"description" => __("Cookie Consent Management as it should be", "really-simple-ssl"),
				'read_more' => false,
			]
		];

		$items = [];
		$items[] = [
			"id" => 'ssl_enabled',
			"title" => __("SSL has been activated", "really-simple-ssl"),
			"action" => "none",
			"status" => "success",
		];

		$all_enabled = RSSSL()->onboarding->all_recommended_hardening_features_enabled();
		if( !$all_enabled ) {
			$items[] = [
				"title" => __("Enable recommended hardening features in Really Simple SSL", "really-simple-ssl"),
				"id" => "hardening",
				"action" => "activate_setting",
				"current_action" => "none",
				"status" => "warning",
				"button" => __("Enable", "really-simple-ssl"),
			];
		} else {
			$items[] = [
				"title" => __("Hardening features are enabled!", "really-simple-ssl"),
				"action" => "none",
				"current_action" => "none",
				"status" => "success",
				"id" => "hardening",
			];
		}

		foreach ($plugins_to_install as $plugin_info) {
			require_once(rsssl_path . 'class-installer.php');
			$plugin = new rsssl_installer($plugin_info["slug"]);
			$premium_active = $plugin_info['constant_premium'] && defined($plugin_info['constant_premium']);
			$free_active = $plugin->plugin_is_downloaded() && $plugin->plugin_is_activated();
			if( $premium_active || $free_active ) {
				$items[] = [
					"id" => $plugin_info['slug'],
					"is_plugin" => true,
					"title" => sprintf(__("%s has been installed!", "really-simple-ssl"), $plugin_info["title"]),
					"action" => "none",
					"current_action" => "none",
					"status" => "success",
				];
			} else if( !$plugin->plugin_is_downloaded() ){
				$items[] = [
					"id" => $plugin_info['slug'],
					"is_plugin" => true,
					"title" => $plugin_info["title"],
					"description" => $plugin_info["description"],
					"read_more" => $plugin_info["read_more"],
					"action" => "install_plugin",
					"current_action" => "none",
					"status" => "warning",
					"button" => __("Install", "really-simple-ssl"),
				];
			} else if ( $plugin->plugin_is_downloaded() && !$plugin->plugin_is_activated() ) {
				$items[] = [
					"id" => $plugin_info['slug'],
					"is_plugin" => true,
					"title" => sprintf(__("Activate our plugin %s", "really-simple-ssl"), $plugin_info["title"]),
					"action" => "activate",
					"current_action" => "none",
					"status" => "warning",
					"button" => __("Activate", "really-simple-ssl"),
				];
			}


		}

		return $items;
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
		foreach ($this->hardening as $h ){
			if ( rsssl_get_option($h)!=1 ) {
				return false;
			}
		}
		return true;
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


