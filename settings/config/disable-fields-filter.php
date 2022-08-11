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
		if ( !rsssl_debug_log_in_default_location()) {
			if (!$field['value'] ) {
				$field['value'] = true;
				$field['disabled'] = true;
			}
			$location = strstr( rsssl_get_debug_log_value(), 'wp-content' );
			$field['help'] = [
				'label' => 'default',
				'text' => __( "Changed debug.log location:", 'really-simple-ssl' ).$location,
			];
		}
	}

	if ( $field_id==='disable_indexing' ){
		if ( !rsssl_directory_indexing_allowed() ) {
			if ( !$field['value'] ) {
				$field['value'] = true;
				$field['disabled'] = true;
				$field['help'] = [
					'label' => 'default',
					'text' => __( "Directory browsing is is already disabled.", 'really-simple-ssl' ),
				];
			}
		}
	}

	if ( $field_id==='disable_anyone_can_register' ){
		if ( !get_option('users_can_register') ) {
			if ( !$field['value'] ) {
				$field['value'] = true;
				$field['disabled'] = true;
				$field['help'] = [
					'label' => 'default',
					'text' => __( "User registration is is already disabled.", 'really-simple-ssl' ),
				];
			}
		}
	}

	return $field;
}
add_filter('rsssl_field', 'rsssl_disable_fields', 10, 2);

///**
// * @param $fields
// *
// * @return mixed
// */
//function rsssl_remove_fields($fields){
//	if ( rsssl_get_server() !== 'apache' ){
//		$index = array_search('block_code_execution_uploads', array_column($fields, 'id') );
//		unset($fields[$index]);
//	}
//	return $fields;
//}
//add_filter('rsssl_fields', 'rsssl_remove_fields', 10, 1);



