<?php
defined('ABSPATH') or die();

function rsssl_menu( $group_id = 'group_general' ){
	$menu_items = [
		'group_general' => [
				[
					"id"    => "main",
					"title" => __( "Main item 1", 'really-simple-ssl' ),
					'sections' => [
						[
							'id' => 'general',
							'title' => __('Visitors', 'really-simple-ssl'),
						],
						[
							'id' => 'item-2',
							'title' => __('Item 2', 'really-simple-ssl'),
						],
					],
				],
			[
				"id"    => "main2",
				"title" => __( "Main item 2", 'really-simple-ssl' ),
				'sections' => [
					[
						'id' => 'general-2',
						'title' => __('Visitors 2', 'really-simple-ssl'),
					],
					[
						'id' => 'item-2',
						'title' => __('Item 2 2', 'really-simple-ssl'),
					],
				],
			],
			]
		];
	$menu_items = apply_filters('rsssl_menu', $menu_items);
	return isset($menu_items[$group_id]) ? $menu_items[$group_id] : array();
}

function rsssl_fields( $menu_id = 'main', $sub_menu_id = false ){
	$fields = array(
		array(
			'id'          => 'mixed_content_fixer',
			'menu_id' => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Field name", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
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