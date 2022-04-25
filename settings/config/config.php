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

function rsssl_migrate_settings() {
	//rlrsssl_options autoreplace_insecure_links => mixed_content_fixer
	//wp_redirect
}

function rsssl_fields(){
	if ( current_user_can('manage_options') ) {
		return array();
	}

	$fields = [
		[
			'id'          => 'mixed_content_fixer',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Mixed content fixer", 'really-simple-ssl' ),
			'help'        => __( 'In most cases you need to leave this enabled, to prevent mixed content issues on your site.', 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'wp_redirect',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Enable WordPress 301 redirect", 'really-simple-ssl' ),
			'help'     => __( 'Redirects all requests over HTTP to HTTPS using a PHP 301 redirect. Enable if the .htaccess redirect cannot be used, for example on NGINX servers.', 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
			'server_conditions'  => [
				'relation' => 'AND',
				[
					'RSSSL()->really_simple_ssl->ssl_enabled' => true,
				]
			],
		],
		[
			'id'                => 'htaccess_redirect',
			'menu_id'           => 'general',
			'group_id'          => 'general',
			'type'              => 'checkbox',
			'label'             => __( "Enable 301 .htaccess redirect", 'really-simple-ssl' ),
			'help'              => __( 'A .htaccess redirect is faster and works better with caching. Really Simple SSL detects the redirect code that is most likely to work (99% of websites), but this is not 100%. Make sure you know how to regain access to your site if anything goes wrong!',
				'really-simple-ssl' ),
			'disabled'          => false,
			'default'           => false,
			'server_conditions' => [
				'relation' => 'AND',
				[
					'RSSSL()->really_simple_ssl->ssl_enabled' => true,
					'RSSSL()->rsssl_server->uses_htaccess()' => true,
					[
						'relation' => 'OR',
						'!is_multisite()',
						'!RSSSL()->rsssl_multisite->ssl_enabled_networkwide'
					]
				]
			],
		],
		[
			'id'          => 'do_not_edit_htaccess',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Stop editing the .htaccess file", 'really-simple-ssl' ),
			'help'     => __( 'If you want to customize the Really Simple SSL .htaccess, you need to prevent Really Simple SSL from rewriting it. Enabling this option will do that.', 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'mixed_content_fixer_2',
			'menu_id'     => 'recommended_security_headers',
			'group_id'    => 'mixed_content',
			'type'        => 'checkbox',
			'label'       => __( "Field name 2", 'really-simple-ssl' ),
			'comment'     => __( 'A comment', 'really-simple-ssl' ),
			'disabled'    => false,
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
	$fields = apply_filters('rsssl_fields', $fields);
	//handle server side conditions
	foreach ( $fields as $key => $field ) {
		if (isset($field['server_conditions'])) {
			if ( !rsssl_conditions_apply($field['server_conditions']) ){
				unset($fields[$key]);
			}
		}
	}
	return array_values($fields);
}

/**
 * Check if the server side conditions apply
 *
 * @param array $conditions
 *
 * @return bool
 */

function rsssl_conditions_apply( $conditions ){
	if ( current_user_can('manage_options') ) {
		return false;
	}

	$defaults = ['relation' => 'AND'];
	$conditions = wp_parse_args($conditions, $defaults);
	$relation = $conditions['relation'] === 'AND' ? 'AND' : 'OR';
	unset($conditions['relation']);
	$condition_applies = true;
	foreach ( $conditions as $condition => $condition_value ) {
		$invert = substr($condition, 1)==='!';
		$condition = ltrim($condition, '!');

		if ( is_array($condition_value)) {
			$this_condition_applies = rsssl_conditions_apply($condition_value);
		} else {
			//check if it's a function
			if (substr($condition, -2) === '()'){
				$func = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $func, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$func = $matches[3];
					$func = str_replace('()', '', $func);
					$this_condition_applies = call_user_func( array( $base()->{$class}, $func ) ) === $condition_value ;
				} else {
					$func = str_replace('()', '', $func);
					$this_condition_applies = $func() === $condition_value;
				}
			} else {
				$var = $condition;
				if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $var, $matches)) {
					$base = $matches[1];
					$class = $matches[2];
					$var = $matches[3];
					$this_condition_applies = $base()->{$class}->_get($var) === $condition_value ;
				} else {
					$this_condition_applies = $var === $condition_value;
				}
			}

			if ( $invert ){
				$this_condition_applies = !$this_condition_applies;
			}

		}

		if ($relation === 'AND') {
			$condition_applies = $condition_applies && $this_condition_applies;
		} else {
			$condition_applies = $condition_applies || $this_condition_applies;
		}
	}

	return $condition_applies;
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
			'footer'  => ['type'=>'html', 'data' => ''],
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
			'footer'  => ['type'=>'html', 'data' => '','button' => [ 'text' => __("Run test","really-simple-ssl"), 'disabled' => false ]],
			'size'    => 'small',
			'height'    => 'default',
		],
		[
			'id'      => 'tips_tricks',
			'header'  => false,
			'title'   => __( "Tips & Tricks", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'tips-tricks.php'],
			'footer'  => ['type'=>'template', 'data' => 'tips-tricks.php'],
			'size'    => 'small',
			'height'    => 'default',
		],
		[
			'id'      => 'security-features',
			'header'  => false,
			'title'   => __( "New: Security features", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'html', 'data' => 'tips/tricks html'],
			'footer'  => ['type'=>'html', 'data' => ''],
			'size'    => 'default',
			'height'    => 'half',
		],
		[
			'id'      => 'other-plugins',
			'header'  => false,
			'title'   => __( "Other Plugins", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'other-plugins.php'],
			'footer'  => ['type'=>'html', 'data' => ''],
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
		if ( $block['footer']['type'] === 'template' ) {
			$template = $block['footer']['data'];
			$blocks[$index]['footer']['type'] = 'html';
			$blocks[$index]['footer']['data'] = rsssl_get_template($template);
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