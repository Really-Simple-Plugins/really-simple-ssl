<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'wp_pre_insert_user_data', 'rsssl_pre_insert_user_data_filter', 10, 4 );

/**
 * @param $data
 * @param $update
 * @param $user_id
 * @param $userdata
 *
 * @return false|mixed
 *
 * Filter user registration
 */
function rsssl_pre_insert_user_data_filter( $data, $update, $user_id, $userdata ) {

	// If login = display_name, do not add user
	if ( $userdata->user_login === $userdata->display_name ) {
		// Add notice

		// Return false
		return false;
	}

	return $data;
}