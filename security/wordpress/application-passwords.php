<?php defined( 'ABSPATH' ) or die();
/**
 * @return void
 * Disable application passwords
 */
$set_to_value = '__return_false';
$deactivate_list = get_option('rsssl_deactivate_list', []);
if ( in_array('application_passwords', $deactivate_list )){
	$set_to_value = '__return_true';
	unset($deactivate_list['application_passwords']);
}
add_filter( 'wp_is_application_passwords_available', $set_to_value );

///**
// * @return void
// *
// * Check if REST response contains the 'authorization' header. If so, app passwords have been enabled
// */
//
//function rsssl_test_authorization_header() {
//	if ( function_exists('curl_init' ) && !get_option('rsssl_test_authorization_header_failed') ) {
//		// Fire off a request to the root REST URL to check for the 'authorization' header
//		$response = wp_remote_get( get_rest_url(), array( 'sslverify' => false, 'timeout' => 1 ) );
//
//		if ( isset( $response->errors ) ) {
//			update_option('rsssl_test_authorization_header_failed', true, false );
//		} else {
//			update_option('rsssl_test_authorization_header_passed', true, false );
//		}
//	}
//}
//add_action('init', 'rsssl_test_authorization_header');

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
