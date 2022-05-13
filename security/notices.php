<?php defined( 'ABSPATH' ) or die();

/**
 * Add notice is display name is the same as login
 *
 * @param array $notices
 *
 * @return array
 *
 */

function rsssl_display_name_is_login_name( $notices ) {
	$notices['display_name_is_login'] = array(
		'condition' => ['rsssl_display_name_equals_login'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("Your display name is the same as your login. This is a security risk. We recommend to change your display name to something else.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_display_name_is_login_name' );

function rsssl_debug_log_notice( $notices ) {
	$notices['debug-log-notice'] = array(
		'condition' => ['rsssl_is_debug_log_enabled', 'rsssl_debug_log_in_default_location'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'change_debug_log_location',
				'msg' => __("Errors are logged to default debug.log location.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}
add_filter('rsssl_notices', 'rsssl_debug_log_notice' );
/**
 * @return void
 *
 * User id 1 exists, user enumeration allowed notice
 */
function rsssl_user_id_one_enumeration( $notices ) {
	$notices['user_id_one'] = array(
		'condition' => ['rsssl_id_one_no_enumeration'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("User id 1 exists and user enumeration hasn't been disabled.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_user_id_one_enumeration');
/**
 * @return void
 *
 * Username 'admin' changed notice
 */
function rsssl_admin_username_exists( $notices ) {
	$notices['username_admin_exists'] = array(
		'condition' => ['rsssl_has_admin_user'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'rename_admin_user',
				'msg' => __("A Username 'admin' exists", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;

}
add_filter('rsssl_notices', 'rsssl_admin_username_exists');

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_code_execution_uploads_notice( $notices ) {
	$notices['code-execution-uploads-allowed'] = array(
		'callback' => 'rsssl_code_execution_allowed',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'block_code_execution_uploads',
				'msg' => __("Code execution allowed in uploads folder.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}
add_filter('rsssl_notices', 'rsssl_code_execution_uploads_notice');