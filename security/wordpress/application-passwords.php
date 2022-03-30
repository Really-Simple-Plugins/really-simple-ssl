<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action('application_password_did_authenticate', 'rsssl_application_password_success');
add_action('application_password_failed_authentication', 'rsssl_application_password_fail');

add_action('template_redirect', 'rsssl_maybe_allow_application_passwords');

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
	if ( rsssl_get_option('application_passwords' ) === 1 ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	} else {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
	}
}

function rsssl_application_password_success() {

	$data = array(
		'type' => 'application_password',
		'action' => 'authenticated',
		'referer' => '',
		'user_id' => rsssl_get_user_id(),
	);

	rsssl_log_to_learning_mode_table($data);
}

function rsssl_application_password_fail() {

	$data = array(
		'type' => 'application_password',
		'action' => 'failed',
		'referer' => '',
		'user_id' => rsssl_get_user_id(),
	);

	rsssl_log_to_learning_mode_table($data);
}

add_action('admin_init', 'rsssl_application_passwords_available');