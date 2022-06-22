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
						'group_id' => 'general',
						'title' => __('General', 'really-simple-ssl'),
						'intro' => __("An introduction on some cool stuff", "really-simple-ssl"),
						'step' => 1,
						'groups' => [
//							[
//								'id' => 'general',
//								'title' => __('General', 'really-simple-ssl'),
//								'intro' => __("An introduction on some cool stuff", "really-simple-ssl"),
//							],
							[
								'id' => 'general_2',
								'title' => __('General 2', 'really-simple-ssl'),
								'intro' => __("Group intro", "really-simple-ssl"),
							]
						],
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
	//upgrade both site and network settings

	//rlrsssl_options autoreplace_insecure_links => mixed_content_fixer
	//wp_redirect
	//htaccess_redirect
	//do_not_edit_htaccess
	//switch_mixed_content_fixer_hook
	//dismiss_all_notices
}

function rsssl_fields(){
	if ( !current_user_can('manage_options') ) {
		return [];
	}

	$fields = [
		[
			'id'          => 'mixed_content_fixer',
			'menu_id'     => 'mixed_content_scan',
			'group_id'    => 'mixed_content_scan',
			'type'        => 'checkbox',
			'label'       => __( "Mixed content fixer", 'really-simple-ssl' ),
			'help'        => [
								'label' => 'default',
								'text' => __( 'In most cases you need to leave this enabled, to prevent mixed content issues on your site.', 'really-simple-ssl' ),
							 ],
			'disabled'    => false,
			'default'     => true,
			'new_features_block'     => true,
			'networkwide' => false,
		],
		[
			'id'          => 'wp_redirect',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Enable WordPress 301 redirect", 'really-simple-ssl' ),
			'help'        => [
								'label' => 'default',
								'text' => __( 'Redirects all requests over HTTP to HTTPS using a PHP 301 redirect. Enable if the .htaccess redirect cannot be used, for example on NGINX servers.', 'really-simple-ssl' ),
							],
			'disabled'    => false,
			'default'     => false,
			'server_conditions'  => [
				'relation' => 'AND',
				[
					'RSSSL()->really_simple_ssl->ssl_enabled' => true,
				]
			],
			'new_features_block'     => true,
			'networkwide' => false,

		],
		[
			'id'                => 'htaccess_redirect',
			'menu_id'           => 'general',
			'group_id'          => 'general',
			'type'              => 'checkbox',
			'label'             => __( "Enable 301 .htaccess redirect", 'really-simple-ssl' ),
			'help'              => [
									'label' => 'default',
									'text' => __( 'A .htaccess redirect is faster and works better with caching. Really Simple SSL detects the redirect code that is most likely to work (99% of websites), but this is not 100%. Make sure you know how to regain access to your site if anything goes wrong!',
									'really-simple-ssl' ),
									],
			'disabled'          => false,
			'default'           => false,
			//when enabled networkwide, it's handled on the network settings page
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
			'networkwide' => false,
		],
		[
			'id'          => 'do_not_edit_htaccess',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Stop editing the .htaccess file", 'really-simple-ssl' ),
			'help'        => [
							'label' => 'default',
							'text' => __( 'If you want to customize the Really Simple SSL .htaccess, you need to prevent Really Simple SSL from rewriting it. Enabling this option will do that.', 'really-simple-ssl' ),
							],
			'disabled'    => false,
			'default'     => false,
			//on multisite this setting can only be set networkwide
			'server_conditions' => [
				'relation' => 'AND',
				[
					'RSSSL()->rsssl_server->uses_htaccess()' => true,
					'!is_multisite()',
				]
			],
		],
		[
			'id'          => 'switch_mixed_content_fixer_hook',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Fire mixed content fixer with different method", 'really-simple-ssl' ),
			'help'        => [
							'label' => 'default',
							'title' => __( "Fire mixed content fixer with different method", 'really-simple-ssl' ),
							'text'  => __( 'If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.', 'really-simple-ssl' ),
							],
			'disabled'    => false,
			'default'     => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'mixed_content_fixer' => 1,
				]
			],
		],
		[
			'id'          => 'dismiss_all_notices',
			'menu_id'     => 'general',
			'group_id'    => 'general_2',
			'type'        => 'checkbox',
			'label'       => __( "Dismiss all Really Simple SSL notices", 'really-simple-ssl' ),
			'help'        => [
							'label' => 'default',
							'text' => __( "Enable this option to permanently dismiss all +1 notices in the 'Your progress' tab'", 'really-simple-ssl' ),
							],
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'high_contrast',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'checkbox',
			'label'       => __( "Enable High Contrast mode", 'really-simple-ssl' ),
			'help'        => [
							'label' => 'default',
							'text' => __( "If enabled, all the Really Simple SSL pages within the WordPress admin will be in high contrast", 'really-simple-ssl' ),
							],
			'disabled'    => false,
			'default'     => false,
		],

        [
			'id'          => 'disable_anyone_can_register',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable \"anyone can register\"", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => rsssl_is_user_registration_enabled(),
		],
        [
			'id'          => 'xmlrpc',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable xmlrpc", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'disable_http_methods',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable HTTP methods", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'disable_file_editing',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable file editing", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'          => 'block_code_execution_uploads',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable code execution in uploads folder", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'hide_wordpress_version',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Hide WordPress version", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'disable_login_feedback',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable login feedback", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'rename_db_prefix',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Rename your database prefix", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'disable_application_passwords',
			'menu_id'     => 'application_passwords',
			'type'        => 'checkbox',
			'label'       => __( "Disable application passwords", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'disable_user_enumeration',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable user enumeration", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'disable_rss_feeds',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable RSS feeds (improve disable user enumeration)", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'change_debug_log_location',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Change debug.log location", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'disable_indexing',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Disable directory indexing", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
        [
			'id'          => 'rename_admin_user',
			'menu_id'     => 'hardening',
			'type'        => 'checkbox',
			'label'       => __( "Rename user 'admin'", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
	];
	$fields = apply_filters('rsssl_fields', $fields);

	foreach ( $fields as $key => $field ) {
		$field = wp_parse_args($field, ['id'=>false, 'visible'=> true, 'disabled'=>false, 'new_features_block' => false ]);
		//handle server side conditions
		if (isset($field['server_conditions'])) {
			if ( !rsssl_conditions_apply($field['server_conditions']) ){
				unset($fields[$key]);
				continue;
			}
		}
		$field['value'] = rsssl_get_option($field['id']);
		$fields[$key] = apply_filters('rsssl_field', $field, $field['id']);
	}
	$fields = apply_filters('rsssl_fields_values', $fields);
	return array_values($fields);
}


function rsssl_blocks(){
	$blocks = [
		[
			'id'      => 'progress',
			'title'   => __( "Progress", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'controls' => [
				'type' => 'react', 'data' => 'ProgressHeader'
			],
			'content' => ['type'=>'react', 'data' => 'ProgressBlock'],
			'footer'  => ['type'=>'template', 'data' => 'progress-footer.php'],
			'class'    => ' rsssl-column-2',
		],
		[
			'id'      => 'ssllabs',
			'controls' => false,
			'title'   => __( "SSL Labs", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => [ 'type' => 'test', 'data' => 'ssltest', 'interval'=>1000 ],
			'footer'  => ['type'=>'html', 'data' => '','button' => [ 'text' => __("Check SSL Health","really-simple-ssl"), 'disabled' => false ]],
			'class'    => '',
		],
		[
			'id'      => 'security-features',
			'controls'  => false,
			'title'   => __( "New: Security features", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'react', 'data' => 'SecurityFeaturesBlock'],
			'footer'  => ['type'=>'html', 'data' => ''],
			'class'    => '',
		],
		[
			'id'      => 'tips_tricks',
			'controls'  => false,
			'title'   => __( "Tips & Tricks", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'tips-tricks.php'],
			'footer'  => ['type'=>'template', 'data' => 'tips-tricks-footer.php'],
			'class'    => ' rsssl-column-2',
		],
		[
			'id'      => 'other-plugins',
			'controls'  => false,
			'title'   => __( "Other Plugins", 'really-simple-ssl' ),
			'help'    => __( 'A help text', 'really-simple-ssl' ),
			'content' => ['type'=>'template', 'data' => 'other-plugins.php'],
			'footer'  => ['type'=>'html', 'data' => ''],
			'class'    => ' rsssl-column-2 no-border no-background',
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

function rsssl_is_user_registration_enabled() {
    if ( get_option('users_can_register') !== false ) {
        return true;
    }

    return false;


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