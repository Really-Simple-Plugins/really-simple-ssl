<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action('application_password_did_authenticate', 'rsssl_application_password_success');
add_action('application_password_failed_authentication', 'rsssl_application_password_fail');
add_action('application_password_is_api_request', 'rsssl_maybe_allow_application_passwords' );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'rsssl_application_passwords_allowed', 50, 3);
}

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_application_passwords_allowed( $notices ) {
	$notices['application-passwords'] = array(
		'callback' => 'rsssl_application_passwords_available',
		'score' => 5,
		'output' => array(
			'_true_' => array(
				'msg' => __("Application passwords enabled.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * @return bool
 * Check if application passwords are available
 */
function rsssl_application_passwords_available() {

	if ( wp_is_application_passwords_available() ) {
		return true;
	}

	return false;
}

/**
 * @return void
 * Enable or disable application passwords
 */
function rsssl_maybe_allow_application_passwords() {
	if ( rsssl_get_option('rsssl_disable_application_passwords' ) !== false ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	} else {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
	}
}

/**
 * @return void
 *
 * Check if REST response contains the 'authorization' header. If so, app passwords have been enabled
 */
function rsssl_test_authorization_header() {
	if ( function_exists('curl_init' ) && ! get_option('rsssl_test_authorization_header_failed') ) {
		// Fire off a request to the root REST URL to check for the 'authorization' header
		$response = wp_remote_get( get_rest_url(), array( 'sslverify' => false, 'timeout' => 1 ) );

		if ( isset( $response->errors ) ) {
			update_option('rsssl_test_authorization_header_failed', true );
		} else {
			update_option('rsssl_test_authorization_header_passed', true );
		}
	}
}

add_action('init', 'rsssl_test_authorization_header');

/**
 * @return void
 * Log application password success
 */
function rsssl_application_password_success() {

	$data = array(
		'type' => 'application_password',
		'action' => 'authenticated',
		'referer' => '',
		'user_id' => rsssl_get_user_id(),
	);

	rsssl_log_to_learning_mode_table($data);
}

/**
 * @return void
 * Log application password failure
 */
function rsssl_application_password_fail() {

	$data = array(
		'type' => 'application_password',
		'action' => 'failed',
		'referer' => '',
		'user_id' => rsssl_get_user_id(),
	);

	rsssl_log_to_learning_mode_table($data);
}