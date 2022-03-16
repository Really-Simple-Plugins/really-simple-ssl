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
	];
	return apply_filters('rsssl_fields', $fields);
}

function rsssl_blocks(){
	$blocks = [
		[
			'id'      => 'tasks',
			'url'     => false,
			'title'   => __( "Progress", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'html', 'data' => '<div>This is some html</div>'],
			'footer'  => [
				'html' => '<div>Footer html, no button</div>',
			],
			'size'    => 'default',
		],
		[
			'id'      => 'ssllabs',
			'url'     => 'https://really-simple-ssl.com/instructions',
			'title'   => __( "SSL Labs", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => [ 'type' => 'test', 'data' => 'ssltest', 'interval'=>1000 ],
			'footer'  => [
					'button' => [ 'text' => __("Run test","really-simple-ssl"), 'action' => 'SslLabs', 'disabled' => false ],
					'html' => '<div>Footer html</div>',
					],
			'size'    => 'small',
		],
	];
	return apply_filters('rsssl_blocks', $blocks);
}