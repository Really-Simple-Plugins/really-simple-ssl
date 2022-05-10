<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'rsssl_display_name_is_login_name', 50, 4);
}

/**
 * @param $notices
 *
 * @return mixed
 *
 * Add notice is display name is the same as login
 */
function rsssl_display_name_is_login_name( $notices ) {
	$notices['display-name-is-login'] = array(
		'condition' => 'rsssl_display_name_equals_login',
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'same' => array(
				'msg' => __("Your display name is the same as your login. This is a security risk. We recommend to change your display name to something else.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * @return bool
 *
 * Check if display name is the same as login
 */
function rsssl_display_name_equals_login() {

	$user = wp_get_current_user();

	if ( $user->data->user_login === $user->data->display_name ) {
		return 'same';
	}

	return false;

}