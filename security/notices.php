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
				'msg' => __("The following admin users have set their display name equal to their login name: ", "really-simple-ssl") . "<b>" . rsssl_list_users_where_display_name_is_login_name() . "</b>"
				. ". " . __("We recommend these users to change their display name.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_display_name_is_login_name' );

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