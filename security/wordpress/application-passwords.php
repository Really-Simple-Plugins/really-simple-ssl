<?php defined( 'ABSPATH' ) or die();
/**
 * Disable application passwords
 */
$set_to_value = '__return_false';
if ( rsssl_is_in_deactivation_list('application-passwords') ){
	$set_to_value = '__return_true';
	rsssl_remove_from_deactivation_list('application-passwords');
}
add_filter( 'wp_is_application_passwords_available', $set_to_value );

/**
 * @return void
 * Log application password success
 */
function rsssl_application_password_success() {
	$data = array(
		'type' => 'application_password',
		'action' => 'authenticated',
		'referer' => '',
		'user_id' => get_current_user_id(),
	);
	rsssl_log_to_learning_mode_table($data);
}
add_action('application_password_did_authenticate', 'rsssl_application_password_success');

/**
 * @return void
 * Log application password failure
 */
function rsssl_application_password_fail() {

	$data = array(
		'type' => 'application_password',
		'action' => 'failed',
		'referer' => '',
		'user_id' => get_current_user_id(),
	);

	rsssl_log_to_learning_mode_table($data);
}
add_action('application_password_failed_authentication', 'rsssl_application_password_fail');
