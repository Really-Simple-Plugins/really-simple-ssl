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
		add_action( 'rest_api_init', array($this, 'onboarding_rest_route'), 10 );
	}

	static function this() {
		return self::$_this;
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
		register_rest_route( 'reallysimplessl/v1', 'override_ssl_detection', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'override_ssl_detection' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		) );
	}

	/**
	 * Update SSL detection overridden option
	 */

	public function override_ssl_detection() {
		error_log("override SSL");

		if ( ! current_user_can( 'manage_options') ) return;

		update_option('rsssl_ssl_detection_overridden', false, false );
		exit;
	}

	/**
	 * Logic if the activation notice should be shown
	 */

	function show_notice_activate_ssl() {

		if ( ! RSSSL()->really_simple_ssl->ssl_enabled ) {
			return false;
		}
		if ( defined( "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE" ) && RSSSL_DISMISS_ACTIVATE_SSL_NOTICE ) {
			return false;
		}

		//for multisite, show only activate when a choice has been made to activate networkwide or per site.
		if ( is_multisite() && ! RSSSL()->rsssl_multisite->selected_networkwide_or_per_site ) {
			return false;
		}

		//on multisite, only show this message on the network admin. Per site activated sites have to go to the settings page.
		//otherwise sites that do not need SSL possibly get to see this message.
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


