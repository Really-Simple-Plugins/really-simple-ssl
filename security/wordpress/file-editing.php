<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
	add_filter('rsssl_notices', 'file_editing_notice', 50, 3);
}

function file_editing_notice( $notices ) {
	$notices['file-editing'] = array(
		'callback' => 'rsssl_file_editing_notice',
		'score' => 5,
		'output' => array(
			'allowed' => array(
				'msg' => __("File editing is enabled. Consider adding the 'DISALLOW_FILE_EDIT' constant in your wp-config.php file.", "really-simple-ssl"),
//					'url' => 'https://wordpress.org/support/article/editing-wp-config-php/#disable-the-plugin-and-theme-editor',
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * @return string
 * Add a notice for this integration
 */
function rsssl_file_editing_notice()
{
	if ( defined('DISALLOW_FILE_EDIT' ) ) {
		return 'not-allowed';
	}

	return 'allowed';
}

/**
 * @return void
 *
 * Disable file editing
 */
function rsssl_disable_file_editing() {
	if ( ! defined('DISALLOW_FILE_EDIT' ) ) {
		define('DISALLOW_FILE_EDIT', true );
	}
}
add_action("init", "rsssl_disable_file_editing");