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


	return $field;
}
//add_filter('rsssl_field', 'rsssl_disable_fields', 10, 2);

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



