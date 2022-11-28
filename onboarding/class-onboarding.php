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

	public function handle_onboarding_request($data, $test, $request){
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}
//		delete_option('rsssl_network_activation_status');
//		delete_option("rsssl_onboarding_dismissed");
		switch( $test ){
			case 'override_ssl_detection':
				$data = $this->override_ssl_detection($request);
				break;
			case 'activate_ssl':
				$data = RSSSL()->admin->activate_ssl($request);
				break;
			case 'activate_ssl_networkwide':
				$data = RSSSL()->multisite->process_ssl_activation_step();
				break;
			case 'get_modal_status':
				$data =  ["dismissed" => !$this->show_onboarding_modal()];
				break;
			case 'dismiss_modal':
				$this->dismiss_modal($request);
				$data = ['success'=>true];
				break;
		}

		return $data;
	}

	/**
	 * @param $data
	 * @param $action
	 * @param $request
	 *
	 * @return array|bool[]|false|mixed
	 */
	public function handle_onboarding_action($data, $action, $request){
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}
		$error = false;
		$request_data = $request->get_json_params();
		$next_action = 'none';
		switch( $action ){
			case 'override_ssl_detection':
				$data = $this->override_ssl_detection($request);
				break;
			case 'install_plugin':
				require_once(rsssl_path . 'class-installer.php');
				$plugin = new rsssl_installer(sanitize_title($request_data['id']));
				$success = $plugin->download_plugin();
				$data = [
					'next_action' => 'activate',
					'success' => $success
				];
				break;
			case 'activate':
				require_once(rsssl_path . 'class-installer.php');
				$plugin = new rsssl_installer(sanitize_title($request_data['id']));
				$success = $plugin->activate_plugin();
				$data = [
					'next_action' => 'completed',
					'success' => $success
				];
				break;
			case 'activate_setting':
				foreach ($this->hardening as $h ){
					rsssl_update_option($h, true);
				}
				$data = [
					'next_action' => 'none',
					'success' => true,
				];
		}

		return $data;
	}

	/**
	 * Toggle modal status
	 *
	 * @param $request
	 *
	 * @return void
	 */
	public function dismiss_modal($request){
		if (!rsssl_user_can_manage()) return;
		$data = json_decode($request['data']);
		$dismiss = boolval($data->dismiss);
		update_option("rsssl_onboarding_dismissed", $dismiss, false);
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
			'callback' => 'rsssl_rest_api_onboarding',
			'permission_callback' => function () {
				return rsssl_user_can_manage();
			}
		) );
	}

	/**
	 * Update SSL detection overridden option
	 */

	public function override_ssl_detection($request) {
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}
		$data = $request->get_params();
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


