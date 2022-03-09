<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'rsssl_db_prefix_notice', 50, 3);
}

/**
 * @param $notices
 * @return mixed
 * Notice function
 */
function rsssl_db_prefix_notice( $notices ) {
	$notices['db-prefix-notice'] = array(
		'callback' => 'rsssl_check_db_prefix',
		'score' => 5,
		'output' => array(
			'not-default' => array(
				'msg' => __("Database prefix is not default. Awesome!", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
			'default' => array(
				'msg' => __("Database prefix set to default wp_", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

function rsssl_check_db_prefix() {
	global $wpdb;

	if ( $wpdb->prefix !== 'wp_' ) {
		return 'not-default';
	}
	else {
		return 'default';
	}
}

function rsssl_rename_db_prefix() {

}

//add_action('admin_init', 'rsssl_detect_db_prefix');