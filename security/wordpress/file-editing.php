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