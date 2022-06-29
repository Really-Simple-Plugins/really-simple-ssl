<?php defined( 'ABSPATH' ) or die();

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
 * @return void
 *
 * Username 'admin' changed notice
 */
function rsssl_admin_user_renamed_user_enumeration_enabled( $notices ) {
	$notices['admin_user_renamed_user_enumeration_enabled'] = array(
		'condition' => ['check_admin_user_renamed_and_enumeration_disabled'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'highlight_field_id' => 'disable_user_enumeration',
				'msg' => __("To prevent attackers from identifying the renamed administrator user you should activate the 'Disable User Enumeration' setting.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;

}
add_filter('rsssl_notices', 'rsssl_admin_user_renamed_user_enumeration_enabled');

