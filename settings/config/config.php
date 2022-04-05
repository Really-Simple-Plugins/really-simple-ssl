<?php
defined('ABSPATH') or die();

function rsssl_menu( $group_id = 'settings' ){
	$menu_items = [
			[
				"id"    => "settings",
				"title" => __( "Settings", 'really-simple-ssl' ),
				"is_wizard" => false,
				'menu_items' => [
					[
						'id' => 'general',
						'title' => __('General', 'really-simple-ssl'),
						'intro' => __("An introduction on some cool stuff", "really-simple-ssl"),
						'step' => 1,
					],
					[
						'id' => 'mixed_content_scan',
						'title' => __('Mixed Content Scan', 'really-simple-ssl'),
						'menu_items' => [
							[
								'id' => 'recommended_security_headers',
								'title' => __('Sub mixed content 1', 'really-simple-ssl'),
							],
							[
								'id' => 'recommended_security_headers',
								'title' => __('Sub mixed content 2', 'really-simple-ssl'),
							],
						],
						'step' => 1,
					],
					[
						'id' => 'recommended_security_headers',
						'title' => __('Recommended Security Headers', 'really-simple-ssl'),
						'step' => 1,
					],
				],
			],
			[
				"id"    => "letsencrypt",
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
			'menu_id'     => 'general',
			'group_id'    => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "Mixed content fixer", 'really-simple-ssl' ),
			'help'     => __( 'A help text about the mixed content fixer ', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'wp_redirect',
			'menu_id'     => 'general',
			'group_id'    => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "WP Redirect", 'really-simple-ssl' ),
			'help'     => __( 'A help text about the wp redirect', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_2',
			'menu_id'     => 'recommended_security_headers',
			'group_id'    => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "Field name 2", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_3',
			'menu_id'     => 'recommended_security_headers',
			'group_id'    => 'mixed_content_2',
			'type'        => 'checkbox',
			'label'       => __( "Field name 3, group 2", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_3',
			'menu_id'     => 'recommended_security_headers',
			'type'        => 'checkbox',
			'label'       => __( "Field name 3", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => true,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_4',
			'menu_id'     => 'recommended_security_headers',
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
			'id'      => 'progress',
			'title'   => __( "Progress", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'header' => [
				'type' => 'react', 'data' => 'ProgressHeader'
			],
			'content' => ['type'=>'react', 'data' => 'ProgressBlock'],
			'footer'  => [
				'html' => '',
			],
			'size'    => 'default',
			'height'    => 'default',
		],
		[
			'id'      => 'ssllabs',
			'header' => [
				'type' => 'url', 'data' => 'https://really-simple-ssl.com/instructions'
			],
			'title'   => __( "SSL Labs", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => [ 'type' => 'test', 'data' => 'ssltest', 'interval'=>1000 ],
			'footer'  => [
				'button' => [ 'text' => __("Run test","really-simple-ssl"), 'disabled' => false ],
				'html' => '',
			],
			'size'    => 'small',
			'height'    => 'default',
		],
		[
			'id'      => 'tips_tricks',
			'header'  => false,
			'title'   => __( "Tips & Tricks", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'tips-tricks.php'],
			'footer'  => [
				'html' => '<div>Footer html, no button</div>',
			],
			'size'    => 'small',
			'height'    => 'default',
		],
		[
			'id'      => 'security-features',
			'header'  => false,
			'title'   => __( "New: Security features", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'html', 'data' => 'tips/tricks html'],
			'footer'  => [
				'html' => '<div>Footer html, no button</div>',
			],
			'size'    => 'default',
			'height'    => 'half',
		],
		[
			'id'      => 'other-plugins',
			'header'  => false,
			'title'   => __( "Other Plugins", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'other-plugins.php'],
			'footer'  => [
				'html' => '<div>Footer html, no button</div>',
			],
			'size'    => 'default',
			'height'    => 'half',
		],
	];
	$blocks = apply_filters('rsssl_blocks', $blocks);
	foreach ($blocks as $index => $block ) {
		if ( $block['content']['type'] === 'template' ) {
			$template = $block['content']['data'];
			$blocks[$index]['content']['type'] = 'html';
			$blocks[$index]['content']['data'] = rsssl_get_template($template);
		}
	}

	return $blocks;
}


/**
 * Render html based on template
 *
 * @param string $template
 *
 * @return string
 */

function rsssl_get_template($template) {
	if ( !current_user_can('manage_options') ) {
		return '';
	}
	$html='';
	$file = trailingslashit(rsssl_path) . 'settings/templates/' .$template;
	if ( file_exists($file)  ) {
		ob_start();
		require $file;
		$html = ob_get_clean();
	}

	return $html;
}