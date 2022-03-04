<?php
defined('ABSPATH') or die();

function rsssl_menu( $menu_id = 'general' ){
	$menu_items = array(
		'general' =>
			array(
				'step'    => 1,
				'label'   => __( "Menu item", 'really-simple-ssl' ),
			),
			array(
				'step'    => 1,
				'label'   => __( "Menu item 2", 'really-simple-ssl' ),
			)
	);
	$menu_items = apply_filters('rsssl_menu', $menu_items);
	return isset($menu_items[$menu_id]) ? $menu_items[$menu_id] : array();
}

function rsssl_fields( $menu_id = 'general' ){
	$fields = array(
		array(
			'id'       => 'mixed_content_fixer',
			'menu_id'  => 'general',
			'step'     => '1',
			'type'     => 'checkbox',
			'label'    => __( "Field name", 'really-simple-ssl' ),
			'comment'  => __( 'A comment', 'really-simple-ssl' ),
			'disabled' => true,
			'default'  => false,
		),
	);
	$fields = apply_filters('rsssl_fields', $fields);
	$output = array();

	foreach ($fields as $key => $field ){
		if ( $field['menu_id']===$menu_id ) {
			$output[$key] = $field;
		}
	}
	return $output;
}