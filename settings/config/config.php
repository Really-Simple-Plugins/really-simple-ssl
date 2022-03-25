<?php
defined('ABSPATH') or die();

function rsssl_menu( $group_id = 'group_general' ){
	$menu_items = [
			[
				"id"    => "group_general",
				"title" => __( "General settings", 'really-simple-ssl' ),
				"is_wizard" => true,
				'menu_items' => [
					[
						'id' => 'mixed_content',
						'title' => __('Mixed content', 'really-simple-ssl'),
						'step' => 1,
					],
					[
						'id' => 'headers',
						'title' => __('Headers', 'really-simple-ssl'),
						'menu_items' => [
							[
								'id' => 'mixed_content2',
								'title' => __('Mixed content 2', 'really-simple-ssl'),
							],
							[
								'id' => 'headers',
								'title' => __('Headers', 'really-simple-ssl'),
							],
						],
						'step' => 1,

					],
                    [
                        'id' => 'hardening',
						'title' => __('Hardening', 'really-simple-ssl'),
						'menu_items' => [
							[
								'id' => 'application_passwords',
								'title' => __('Disable application passwords', 'really-simple-ssl'),
							],
							[
								'id' => 'code_execution_uploads',
								'title' => __('Disable code execution in uploads folder', 'really-simple-ssl'),
							],
						],
						'step' => 1,

					],
				],
			],
			[
				"id"    => "group_letsencrypt",
				"title" => __( "lets encrypt menu", 'really-simple-ssl' ),
				'menu_items' => [
					[
						'id' => 'system-check',
						'title' => __('system check', 'really-simple-ssl'),
					],
					[
						'id' => 'general',
						'title' => __('General', 'really-simple-ssl'),
					],
				],
			],

		];
	$menu_items = apply_filters('rsssl_menu', $menu_items);
	foreach ($menu_items as $index => $menu_item ) {
		if ($menu_item['id']===$group_id) {
			return $menu_item;
		}
	}
	return array();
}

function rsssl_fields(){
	$fields = [
		[
			'id'          => 'mixed_content_fixer',
			'menu_id'     => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "Field name 1", 'really-simple-ssl' ),
			'help'     => __( 'A help text', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_2',
			'menu_id'     => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "Field name 2", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_3',
			'menu_id'     => 'headers',
			'type'        => 'checkbox',
			'label'       => __( "Field name 3", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_4',
			'menu_id'     => 'headers',
			'type'        => 'checkbox',
			'label'       => __( "Field name 4", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
        [
			'id'          => 'application_passwords',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable application passwords", 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
        [
			'id'          => 'code_execution_uploads',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable code execution in uploads folder", 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
	];
	return apply_filters('rsssl_fields', $fields);
}

function rsssl_blocks(){
	$blocks = [
		'tasks' => [
			'id'          => 'tasks',
			'url'        => 'https://really-simple-ssl.com/instructions',
			'title'       => __( "Grid block title", 'really-simple-ssl' ),
			'help'     => __( 'A help text', 'really-simple-ssl' ),
			'html'      => '<div>This is some html</div>',
		],
		'ssllabs' => [
			'id'          => 'ssllabs',
			'url'        => 'https://really-simple-ssl.com/instructions',
			'title'       => __( "Grid block title", 'really-simple-ssl' ),
			'help'     => __( 'A help text', 'really-simple-ssl' ),
			'html'      => '<div>This is some html</div>',
		],
	];
	return apply_filters('rsssl_blocks', $blocks);
}