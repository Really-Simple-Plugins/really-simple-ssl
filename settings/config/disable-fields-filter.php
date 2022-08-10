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

