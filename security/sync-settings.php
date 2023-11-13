<?php
defined('ABSPATH') or die();
/**
 * Conditionally we can decide to disable fields, add comments, and manipulate the value here
 * @param array $field
 * @param string $field_id
 *
 * @return array
 */

function rsssl_disable_fields($field, $field_id){
	/**
	 * If a feature is already enabled, but not by RSSSL, we can simply check for that feature, and if the option in RSSSL is active.
	 * We set is as true, but disabled. Because our react interface only updates changed option, and this option never changes, this won't get set to true in the database.
	 */
	if ( $field_id==='change_debug_log_location' ){
		if ( !rsssl_debug_log_file_exists_in_default_location() ) {
			if ( !rsssl_is_debugging_enabled() ) {
				if ( !$field['value'] ) {
					$field['value'] = true;
					$field['disabled'] = true;
				}
			} else if ( !rsssl_debug_log_value_is_default() ) {
				if ( !$field['value'] ) {
					$field['value'] = true;
					$field['disabled'] = true;
				}
			}
			//if not the default location
			$location = strstr( rsssl_get_debug_log_value(), 'wp-content' );
			if (!empty($location) && rsssl_is_debugging_enabled() && !rsssl_debug_log_value_is_default() ) {
				$field['help'] = [
					'label' => 'default',
					'title' => __( "Debug.log", 'really-simple-ssl' ),
					'text' => __( "Changed debug.log location to:", 'really-simple-ssl' ).$location,
				];
			}

		}

	}

	if ( $field_id==='disable_indexing' ){
		if ( !rsssl_directory_indexing_allowed() && !$field['value']) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='disable_anyone_can_register' ){
		if ( !get_option('users_can_register') && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='disable_http_methods' ){
		if ( !rsssl_http_methods_allowed() && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='disable_file_editing' ){
		if ( defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='block_code_execution_uploads' ){
		if ( !rsssl_code_execution_allowed() && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='disable_xmlrpc' ){
		if ( !rsssl_xmlrpc_enabled() && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	if ( $field_id==='rename_db_prefix' ){
		if ( !rsssl_is_default_wp_prefix() && !$field['value'] ) {
			$field['value'] = true;
			$field['disabled'] = true;
		}
	}

	return $field;
}
add_filter('rsssl_field', 'rsssl_disable_fields', 10, 2);
