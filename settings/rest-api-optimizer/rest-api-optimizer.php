<?php
defined( 'ABSPATH' ) or die();
//check if our optimizer is installed
if ( !defined('rsssl_rest_api_optimizer')) {
	$php = file_get_contents(trailingslashit(plugin_dir_path(__FILE__)).'optimization-code.php');
	if ( is_writable(WPMU_PLUGIN_DIR) ){
		error_log("put file contents");
		file_put_contents(trailingslashit(WPMU_PLUGIN_DIR).'rsssl_rest_api_optimizer.php', $php);
	}
}
