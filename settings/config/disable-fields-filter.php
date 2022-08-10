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
	if ( $field_id==='change_debug_log_location' ){
		if ( !rsssl_debug_log_in_default_location()) {
			if (!$field['value'] ) {
				$field['value'] = true;
				$field['disabled'] = true;
			}
			$location = rsssl_get_debug_log_value();
			$field['help'] = __( "Changed debug.log location:", 'really-simple-ssl' ) . $location;;
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



