<?php
defined( 'ABSPATH' ) or die();

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


/**
 * Username 'admin' changed notice
 * @return array
 */
function rsssl_disable_file_editing_notice( $notices ) {
	$notices['disallow_file_edit_false'] = array(
		'condition' => ['rsssl_file_editing_defined_but_disabled'],
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("The DISALLOW_FILE_EDIT constant is defined and set to false. You can remove it from your wp-config.php.", "really-simple-ssl"),
				'icon' => 'open',
				'dismissible' => true,
				'url' => 'https://really-simple-ssl.com/disallow_file_edit-defined-set-to-false'
			),
		),
	);
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_disable_file_editing_notice');

/**
 * Check if the constant is defined, AND set to false. In that case the plugin cannot override it anymore
 * @return bool
 */
function rsssl_file_editing_defined_but_disabled(){
	return defined( 'DISALLOW_FILE_EDIT' ) && ! DISALLOW_FILE_EDIT;
}