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
	}

	static function this() {
		return self::$_this;
	}

	public function maybe_redirect_to_settings_page() {
		if ( get_transient('rsssl_redirect_to_settings_page' ) ) {
			delete_transient('rsssl_redirect_to_settings_page' );
			if ( !RSSSL()->really_simple_ssl->is_settings_page() ) {
				if ( is_multisite() && is_super_admin() ) {
					wp_redirect( add_query_arg(array('page' => 'really-simple-security'), network_admin_url('settings.php') )  );
					exit;
				} else {
					wp_redirect( add_query_arg(array('page'=>'really-simple-security#dashboard'), admin_url('options-general.php') ) );
					exit;
				}
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
				return current_user_can( 'manage_options' );
			}
		) );
		register_rest_route( 'reallysimplessl/v1', 'activate_ssl', array(
			'methods'  => 'POST',
			'callback' => array( RSSSL()->really_simple_ssl, 'activate_ssl' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		) );

		register_rest_route( 'reallysimplessl/v1', 'activate_ssl_networkwide', array(
			'methods'  => 'POST',
			'callback' => array( RSSSL()->rsssl_multisite, 'process_ssl_activation_step' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		) );
		register_rest_route( 'reallysimplessl/v1', 'override_ssl_detection', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'override_ssl_detection' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		) );
		register_rest_route( 'reallysimplessl/v1', 'onboarding_actions', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'onboarding_actions' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function onboarding_actions($request){
		if (!current_user_can('manage_options')){
			return;
		}
		$error = false;
		$data = $request->get_json_params();
		$next_action = 'none';

		if ( $data['type']==='plugin') {
			require_once(rsssl_path . 'class-installer.php');
			$plugin = new rsssl_installer($data['id']);
			if ( $data['action']==='install_plugin') {
				$success = $plugin->download_plugin();
				$error = !$success;
				$next_action = 'activate';
			} else {
				$success = $plugin->activate_plugin();
				$error = !$success;
				$next_action = 'completed';
			}
		} else if ($data['type']==='setting') {
			if ( $data['id'] ==='hardening' ) {
				foreach ($this->hardening as $h ){
					rsssl_update_option($h, true);
				}
				$next_action = 'completed';
			}
		} else if ( $data['id'] ==='dismiss_onboarding_modal'){
			update_option("rsssl_onboarding_dismissed", true, false);
		}
		$output = [
			'next_action' => $next_action,
			'success' => !$error
		];
		$response = json_encode( $output );
		header( "Content-Type: application/json" );
		echo $response;
		exit;

	}

	/**
	 * Update SSL detection overridden option
	 */

	public function override_ssl_detection() {
		if ( ! current_user_can( 'manage_options') ) {
			return;
		}

		update_option('rsssl_ssl_detection_overridden', true, false );
		exit;
	}

	/**
	 * Logic if the activation notice should be shown
	 */

	function show_notice_activate_ssl() {
		$is_upgrade = get_option('rsssl_upgraded_to_6');
		if ( RSSSL()->really_simple_ssl->ssl_enabled && !$is_upgrade ) {
			return false;
		}

		if ( defined( "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE" ) && RSSSL_DISMISS_ACTIVATE_SSL_NOTICE ) {
			return false;
		}

		//on multisite, only show this message on the network admin.
		if ( is_multisite() && ! is_network_admin() ) {
			return false;
		}

		//don't show in our Let's Encrypt wizard
		if ( isset( $_GET['tab'] ) && $_GET['tab'] === 'letsencrypt' ) {
			return false;
		}

		if ( ! RSSSL()->really_simple_ssl->wpconfig_ok() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}

}


