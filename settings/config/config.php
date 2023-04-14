<?php
defined( 'ABSPATH' ) or die();

function rsssl_menu() {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$menu_items = [
		[
			"id"             => "dashboard",
			"title"          => __( "Dashboard", 'really-simple-ssl' ),
			'default_hidden' => false,
			'menu_items'     => [],
		],
		[
			"id"             => "settings",
			"title"          => __( "Settings", 'really-simple-ssl' ),
			'default_hidden' => false,
			'menu_items'     => [
				[
					'id'       => 'general',
					'group_id' => 'general',
					'title'    => __( 'General', 'really-simple-ssl' ),
					'helpLink' => 'https://really-simple-ssl.com/instructions/about-our-general-settings/?mtm_campaign=instructions&mtm_source=free',
					'groups'   => [
						[
							'id'       => 'general',
							'title'    => __( 'General', 'really-simple-ssl' ),
							'helpLink' => 'https://really-simple-ssl.com/instructions/about-our-general-settings/?mtm_campaign=instructions&mtm_source=free',
						],
						[
							'id' => 'support',
							'title' => __('Premium Support', 'really-simple-ssl'),
							'intro' => __('The following information is attached when you send this form: license key, scan results, your domain, .htaccess file, debug log and a list of active plugins.', 'really-simple-ssl'),
							'premium' => true,
							'premium_text' => __("Get Premium Support with %sReally Simple SSL Pro%s", 'really-simple-ssl'),
							'helpLink'  => 'https://really-simple-ssl.com/instructions/debugging/?mtm_campaign=instructions&mtm_source=free',
							'upgrade'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=premiumsupport&mtm_source=free&mtm_content=upgrade',
							'helpLink_text'  => __('Debugging with Really Simple SSL',"really-simple-ssl"),
						],
					],
				],
				[
					'id'       => 'hardening',
					'title'    => __( 'Hardening', 'really-simple-ssl' ),
					'featured' => true,
					'groups'   => [
						[
							'id'       => 'hardening_basic',
							'helpLink' => 'https://really-simple-ssl.com/instructions/about-hardening-features/?mtm_campaign=instructions&mtm_source=free',
							'title'    => __( 'Hardening', 'really-simple-ssl' ),
						],
						[
							'id'           => 'hardening_extended',
							'premium'      => true,
							'helpLink'     => 'https://really-simple-ssl.com/instructions/about-hardening-features#advanced/?mtm_campaign=instructions&mtm_source=free',
							'upgrade'     => 'https://really-simple-ssl.com/instructions/about-hardening-features#advanced/?mtm_campaign=upgrade&mtm_source=free',
							'title'        => __( 'Advanced Hardening', 'really-simple-ssl' ),
							'premium_text' => __( "Get Advanced Hardening with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
						],
						[
							'id'           => 'hardening_xml',
							'premium'      => true,
							'helpLink'     => 'https://really-simple-ssl.com/instructions/about-hardening-features#xml-rpc?mtm_campaign=instructions&mtm_source=free',
							'upgrade'			 => 'https://really-simple-ssl.com/instructions/about-hardening-features#xml-rpc?mtm_campaign=upgrade&mtm_source=free',
							'title'        => __( 'XML-RPC', 'really-simple-ssl' ),
							'premium_text' => __( "Get XML-RPC with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
						],
					],
				],
				[
					'id'           => 'mixed_content_scan',
					'title'        => __( 'Mixed Content Scan', 'really-simple-ssl' ),
					'premium'      => true,
					'upgrade'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
					'helpLink'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=instructions',
					'premium_text' => __( "Get the Mixed Content Scan with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
					'groups'       => [
						[
							'id'           => 'mixedcontentscan',
							'title'        => __( 'Mixed Content Scan', 'really-simple-ssl' ),
							'helpLink'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
							'upgrade'     => 'https://really-simple-ssl.com/pro/?mtm_campaign=mixedcontent&mtm_source=free&mtm_content=upgrade',
							'premium'      => true,
							'premium_text' => __( "Get the Mixed Content Scan with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
						],
					],
					//example of submenu
//						'menu_items' => [
//							[
//								'id' => 'sub_mixed_content_1',
//								'title' => __('Sub mixed content 1', 'really-simple-ssl'),
//							],
//							[
//								'id' => 'sub_mixed_content_2',
//								'title' => __('Sub mixed content 2', 'really-simple-ssl'),
//							],
//						],
				],
				[
					'id'      => 'recommended_security_headers',
					'title'   => __( 'Recommended Security Headers', 'really-simple-ssl' ),
					'premium' => true,
					'groups'  => [
						[
							'id'                   => 'recommended_security_headers',
							'networkwide_required' => true,
							'premium'              => true,
							'premium_text'         => __( "Get Recommended Security Headers with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=recommendedheaders&mtm_source=free&mtm_content=upgrade',
							'title'                => __( 'Recommended Security Headers', 'really-simple-ssl' ),
							'helpLink'             => 'https://really-simple-ssl.com/instructions/about-recommended-security-headers/?mtm_campaign=instructions&mtm_source=free',
						],
					],
				],
				[
					'id'      => 'hsts',
					'title'   => 'HTTP Strict Transport Security',
					'premium' => true,
					'groups'  => [
						[
							'id'                   => 'hsts',
							'premium'              => true,
							'networkwide_required' => true,
							'premium_text'         => __( "Get HTTP Strict Transport Security with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=hsts&mtm_source=free&mtm_content=upgrade',
							'title'                => __( 'HTTP Strict Transport Security', 'really-simple-ssl' ),
							'helpLink'             => 'https://really-simple-ssl.com/instructions/about-hsts/?mtm_campaign=instructions&mtm_source=free',
						],
					],
				],
				[
					'id'      => 'permissions_policy',
					'title'   => 'Permissions Policy',
					'premium' => true,
					'groups'  => [
						[
							'id'                   => 'permissions_policy',
							'premium_text'         => __( "Get the Permissions Policy with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=permissionspolicy&mtm_source=free&mtm_content=upgrade',
							'helpLink'             => 'https://really-simple-ssl.com/instructions/about-permissions-policy/?mtm_campaign=instructions&mtm_source=free',
							'networkwide_required' => true,
							'premium'              => true,
							'title'                => 'Permissions Policy',
						],
					],
				],
				[
					'id'      => 'content_security_policy',
					'title'   => 'Content Security Policy',
					'premium' => true,
					'groups'  => [
						[
							'id'                   => 'upgrade_insecure_requests',
							'networkwide_required' => true,
							'premium'              => true,
							'premium_text'         => __( "Get Upgrade Insecure Requests with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=upgradeinsecurerequests&mtm_source=free&mtm_content=upgrade',
							'helpLink'             => 'https://really-simple-ssl.com/instructions/upgrade-insecure-requests/?mtm_campaign=instructions&mtm_source=free',
							'title'                => 'Upgrade Insecure Requests',
						],
						[
							'id'                   => 'frame_ancestors',
							'networkwide_required' => true,
							'premium'              => true,
							'premium_text'         => __( "Get Frame Ancestors with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=frameancestors&mtm_source=free&mtm_content=upgrade',
							'helpLink'             => 'https://really-simple-ssl.com/instructions/frame-ancestors/?mtm_campaign=instructions&mtm_source=free',
							'title'                => 'Frame Ancestors',
						],
						[
							'id'                   => 'content_security_policy',
							'networkwide_required' => true,
							'helpLink'             => 'https://really-simple-ssl.com/instructions/source-directives/',
							'premium'              => true,
							'premium_text'         => __( "Get Source Directives with %sReally Simple SSL Pro%s", 'really-simple-ssl' ),
							'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=csp&mtm_source=free&mtm_content=upgrade',
							'title'                => 'Source Directives',
						]
					],
				],
				[
					'id'                   => 'cross_origin_policy',
					'networkwide_required' => true,
					'premium'              => true,
					'premium_text'         => __( 'Get Cross Origin Policy Headers with %sReally Simple SSL Pro%s', 'really-simple-ssl' ),
					'upgrade'              => 'https://really-simple-ssl.com/pro/?mtm_campaign=cops&mtm_source=free&mtm_content=upgrade',
					'title'                => 'Cross Origin Policy',
					'helpLink'             => 'https://really-simple-ssl.com/instructions/cross-origin-policies/?mtm_campaign=instructions&mtm_source=free',

				],
			],
		],
		[
			"id"             => "letsencrypt",
			'default_hidden' => true,
			"title"          => "Let's Encrypt",
			'intro'          => sprintf( __( 'We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!',
					'really-simple-ssl' ), '<a target="_blank" href="https://really-simple-ssl.com/contact/?mtm_campaign=instructions&mtm_source=free">', '</a>' ) .
			                    sprintf( __( ' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.',
				                    'really-simple-ssl' ), '<a target="_blank" href="https://really-simple-ssl.com/install-ssl-certificate/?mtm_campaign=instructions&mtm_source=free">', '</a>',
				                    '<a target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/">', '</a>' ),

			'menu_items' => [
				[
					'id'         => 'le-system-status',
					'title'      => __( 'System Status', 'really-simple-ssl' ),
					'intro'      => __( 'Letʼs Encrypt is a free, automated and open certificate authority brought to you by the nonprofit Internet Security Research Group (ISRG).',
						'really-simple-ssl' ),
					'helpLink'   => 'https://really-simple-ssl.com/about-lets-encrypt/?mtm_campaign=letsencrypt&mtm_source=free',
					'tests_only' => true,
				],
				[
					'id'    => 'le-general',
					'title' => __( 'General Settings', 'really-simple-ssl' ),
				],
				[
					'id'    => 'le-hosting',
					'title' => __( 'Hosting', 'really-simple-ssl' ),
					'intro' => __( 'Below you will find the instructions for different hosting environments and configurations. If you start the process with the necessary instructions and credentials the next view steps will be done in no time.',
						'really-simple-ssl' ),
				],
				[
					'id'         => 'le-directories',
					'title'      => __( 'Directories', 'really-simple-ssl' ),
					'tests_only' => true,
				],
				[
					'id'         => 'le-dns-verification',
					'title'      => __( 'DNS verification', 'really-simple-ssl' ),
					'tests_only' => true,
				],
				[
					'id'         => 'le-generation',
					'title'      => __( 'Generation', 'really-simple-ssl' ),
					'tests_only' => true,
				],
				[
					'id'         => 'le-installation',
					'title'      => __( 'Installation', 'really-simple-ssl' ),
					'tests_only' => true,
				],
				[
					'id'         => 'le-activate_ssl',
					'title'      => __( 'Activate', 'really-simple-ssl' ),
					'tests_only' => true,
				],
			],
		],
	];

	return apply_filters( 'rsssl_menu', $menu_items );
}

function rsssl_fields( $load_values = true ) {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$fields = [
		[
			'id'       => 'ssl_enabled',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => 'ssl_enabled',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'site_has_ssl',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'review_notice_shown',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'               => 'redirect',
            'menu_id'          => 'general',
			'group_id'         => 'general',
			'type'             => 'select',
			'tooltip'  => __( "Changing redirect methods should be done with caution. Please make sure you have read our instructions beforehand at the right-hand side.", 'really-simple-ssl' ),
			'label'            => __( "Redirect method", 'really-simple-ssl' ),
			'warning'     			=> true,
			'options'          => [
				'none'         => __( "No redirect", "really-simple-ssl" ),
				'wp_redirect'  => __( "301 PHP redirect", "really-simple-ssl" ),
				'htaccess'     => __( "301 .htaccess redirect (read instructions first)", "really-simple-ssl" ),
			],
			'help'             => [
				'label' => 'default',
				'title' => __( "Redirect method", 'really-simple-ssl' ),
				'text'  => __( 'Redirects your site to https with a SEO friendly 301 redirect if it is requested over http.', 'really-simple-ssl' ),
			],
            'email'            => [
                'title'   => __( "Settings update: .htaccess redirect", 'really-simple-ssl' ),
                'message' => __( "The .htaccess redirect has been enabled on your site. If the server configuration is non-standard, this might cause issues. Please check if all pages on your site are functioning properly.", 'really-simple-ssl' ),
                'url'     => 'https://really-simple-ssl.com/remove-htaccess-redirect-site-lockout/',
                'condition'  => ['redirect' => 'htaccess']
            ],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'ssl_enabled' => '1',
				]
			],
			'default'          => false,
		],
		[
			'id'       => 'mixed_content_fixer',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __( "Mixed content fixer", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => true,
		],

		[
			'id'               => 'switch_mixed_content_fixer_hook',
			'menu_id'          => 'general',
			'group_id'         => 'general',
			'type'             => 'checkbox',
			'label'            => __( "Mixed content fixer - init hook", 'really-simple-ssl' ),
			'help'             => [
				'label' => 'default',
				'title' => __( "Mixed content fixer - init hook", 'really-simple-ssl' ),
				'text'  => __( 'If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.',
					'really-simple-ssl' ),
			],
			'disabled'         => false,
			'required'         => false,
			'default'          => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'mixed_content_fixer' => 1,
				]
			],
		],
		[
			'id'       => 'admin_mixed_content_fixer',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __( "Mixed content fixer - back-end", "really-simple-ssl" ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'send_notifications_email',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __( "Notifications by email", 'really-simple-ssl' ),
			'tooltip'  => __( "Get notified of important changes, updates and settings. Recommended when using security features.", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'notifications_email_address',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'email',
			'label'    => __( "Email address", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => get_bloginfo('admin_email'),
			'condition_action'   => 'hide',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'send_notifications_email' => 1,
				]
			],
		],
		[
			'id'          => 'send-test-email',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'button',
			'action'         => 'send_test_mail',
			'button_text' => __( "Send", "really-simple-ssl" ),
			'label'       => __( "Send test notification by email", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
			'condition_action'   => 'hide',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'send_notifications_email' => 1,
				]
			],
		],
		[
			'id'       => 'dismiss_all_notices',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __( "Dismiss all notifications", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'          => 'download-system-status',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'button',
			'url'         => trailingslashit( rsssl_url ) . 'system-status.php?download',
			'button_text' => __( "Download", "really-simple-ssl" ),
			'label'       => __( "System status", 'really-simple-ssl' ),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'               => 'delete_data_on_uninstall',
			'menu_id'          => 'general',
			'group_id'         => 'general',
			'type'             => 'checkbox',
			'label'            => __( "Delete all data on plugin deletion", 'really-simple-ssl' ),
			'default'          => false,
		],
		[
			'id'                   => 'do_not_edit_htaccess', //field is removed if not enabled
			'menu_id'              => 'general',
			'group_id'             => 'general',
			'type'                 => 'checkbox',
			'label'                => __( "Stop editing the .htaccess file", 'really-simple-ssl' ),
			'disabled'             => false,
			'default'              => false,
			//on multisite this setting can only be set networkwide
			'networkwide_required' => true,
			'server_conditions'    => [
				'relation' => 'AND',
				[
					'RSSSL()->server->uses_htaccess()' => true,
				]
			],
		],
		[
			'id'       => 'premium_support',
			'menu_id'  => 'general',
			'group_id' => 'support',
			'type'     => 'support',
			'label'    => __( "Premium support", 'really-simple-ssl' ),
			// 'help'        => [
			// 	'label' => 'default',
			// 	'placeholder' => __( "If enabled, all the Really Simple SSL pages within the WordPress admin will be in high contrast", 'really-simple-ssl' ),
			// ],
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'                 => 'disable_anyone_can_register',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable \"anyone can register\"", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'help'               => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-are-hardening-features/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About Hardening", 'really-simple-ssl' ),
				'text'  => __( 'Hardening features limit the possibility of potential weaknesses and vulnerabilities which can be misused.', 'really-simple-ssl' ),
			],
			'new_features_block' => [
				'active'   => __( "User registration is restricted", 'really-simple-ssl' ),
				'inactive' => __( "User registration is not restricted", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#registration?mtm_campaign=readmore&mtm_source=free',
			],
		],
		[
			'id'                 => 'disable_file_editing',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable the built-in file editors", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "File editing is disabled", 'really-simple-ssl' ),
				'inactive' => __( "File editing is enabled", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#file-editing?mtm_campaign=instructions&mtm_source=free',
			],
		],
		[
			'id'                 => 'block_code_execution_uploads',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Prevent code execution in the public 'Uploads' folder", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "Code execution is restricted", 'really-simple-ssl' ),
				'inactive' => __( "Code execution is not restricted", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#code-execution?mtm_campaign=instructions&mtm_source=free',
			],
		],
		[
			'id'       => 'hide_wordpress_version',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_basic',
			'type'     => 'checkbox',
			'label'    => __( "Hide your WordPress version", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'disable_login_feedback',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_basic',
			'type'     => 'checkbox',
			'tooltip'  => __( "By default, WordPress shows if a username or email address exists when a login fails. This will change it to generic feedback.", 'really-simple-ssl' ),
			'label'    => __( "Prevent login feedback", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'                 => 'disable_indexing',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable directory browsing", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "Browsing directories is blocked", 'really-simple-ssl' ),
				'inactive' => __( "Browsing directories is possible", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#browsing-directories?mtm_campaign=instructions&mtm_source=free',
			],
		],
		[
			'id'                 => 'disable_user_enumeration',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable user enumeration", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "User enumeration is restricted", 'really-simple-ssl' ),
				'inactive' => __( "User enumeration is possible", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#user-enumeration?mtm_campaign=instructions&mtm_source=free',
			],
		],
		[
			'id'                 => 'rename_admin_user',
			'menu_id'            => 'hardening',
			'warning'     			=> true,
			'group_id'           => 'hardening_basic',
			'type'               => 'checkbox',
			'label'              => __( "Block the username 'admin'", 'really-simple-ssl' ),
			'email'              => [
				'title'   => __( "Settings update: Username 'admin' renamed", 'really-simple-ssl' ),
				'message' => sprintf(__( "As a security precaution, the username ‘admin’ has been changed on %s. From now on, you can login with '%s' or an email address.", 'really-simple-ssl' ), '{site_url}','{username}'),
				'url'     => 'https://really-simple-ssl.com/instructions/locked-our-after-renaming-the-admin-username/',
				'condition'    => 'rsssl_username_admin_changed',
			],
			'tooltip'            => __( "If the username 'admin' currently exists, you can rename it here. Please note that you can no longer use this username, and should use the new username or an email address",
				'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "Username 'Admin' is not allowed", 'really-simple-ssl' ),
				'inactive' => __( "Username 'Admin' is allowed", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#admin-usernames?mtm_campaign=instructions&mtm_source=free',
			],
		],
		[
			'id'                 => 'new_admin_user_login',
			'menu_id'            => 'hardening',
			'group_id'           => 'hardening_basic',
			'type'               => 'text',
			'label'              => __( "Choose new username to replace 'admin'", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => '',
			'required'           => true,
			'condition_action'   => 'hide',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'rename_admin_user' => 1,
				]
			],
			'server_conditions' => [
				'relation' => 'AND',
				[
					'rsssl_has_admin_user()' => true,
				]
			],

		],
		[
			'id'       => 'disable_xmlrpc',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_basic',
			'type'     => 'checkbox',
			'label'    => __( "Disable XML-RPC", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'block_display_is_login',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_basic',
			'type'     => 'checkbox',
			'label'    => __( "Block user registrations when login and display name are the same", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'disable_http_methods',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_extended',
			'type'     => 'checkbox',
			'tooltip'  => __( "This will limit or fully disable HTTP requests that are not needed, but could be used with malicious intent.", 'really-simple-ssl' ),
			'label'    => __( "Disable HTTP methods", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'rename_db_prefix',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_extended',
			'email'            => [
				'title'   => __( "Settings update: Database prefix changed", 'really-simple-ssl' ),
				'message' => __( "Security through obscurity. Your site is no longer using the default wp_ prefix for database tables. The process has been designed to only complete and replace the tables after all wp_ tables are successfully renamed. In the unlikely event that this does lead to database issues on your site, please navigate to our troubleshooting article.", 'really-simple-ssl' ),
				'url'     => 'https://really-simple-ssl.com/locked-out-after-renaming-admin-user/',
			],
			'tooltip'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
			'warning'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
			'type'     => 'checkbox',
			'label'    => __( "Rename and randomize your database prefix", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'                 => 'change_debug_log_location',
			'group_id'           => 'hardening_extended',
			'menu_id'            => 'hardening',
			'type'               => 'checkbox',
			'tooltip'  => __( "A debug.log is publicly accessibile and has a standard location. This will change the location to a randomly named folder in /wp-content/", 'really-simple-ssl' ),
			'email'            => [
				'title'   => __( "Settings update: Debug.log file relocated", 'really-simple-ssl' ),
				'message' => __( "From now on, the debug.log won’t be publicly accessible whenever wp-debugging is enabled. The debug log will be stored in a randomly named folder in /wp-content/. This prevents possible leakage of sensitive debugging information.", 'really-simple-ssl' ),
				'url'     => 'https://really-simple-ssl.com/instructions/debug-log-has-been-relocated-but-where/',
			],
			'label'              => __( "Change debug.log file location", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'new_features_block' => [
				'active'   => __( "Debug log not publicly accessible", 'really-simple-ssl' ),
				'inactive' => __( "Debug log is now public", 'really-simple-ssl' ),
				'readmore' => 'https://really-simple-ssl.com/instructions/about-hardening-features/#debug-location',
			],
		],
		[
			'id'       => 'disable_application_passwords',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_extended',
			'type'     => 'checkbox',
			'label'    => __( "Disable application passwords", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
        [
            'id'       => 'change_login_url_enabled',
            'menu_id'  => 'hardening',
            'group_id' => 'hardening_extended',
            'warning'  => true,
            'type'     => 'checkbox',
            'tooltip'  => __( "Allows you to enter a custom login URL.", 'really-simple-ssl' ),
            'label'    => __( "Enable Custom login URL", 'really-simple-ssl' ),
            'email'            => [
                'title'   => __( "You have changed your login URL", 'really-simple-ssl' ),
                'message' => __( "Your login URL has changed to {login_url} to prevent common bot attacks on standard login URLs. Learn more about this feature, common questions and measures to prevent any issues.", 'really-simple-ssl' ),
                'url'     => 'https://really-simple-ssl.com/instructions/login-url-changed',
            ],
            'disabled' => false,
            'default'  => false,
        ],
        [
            'id'       => 'change_login_url',
            'menu_id'  => 'hardening',
            'group_id' => 'hardening_extended',
            'type'     => 'text',
            'tooltip'  => __( "Enter a custom login URL. This allows you to log in via this custom URL instead of /wp-admin or /wp-login.php", 'really-simple-ssl' ),
            'placeholder'  => __( "Example: If you want to change your login page from /wp-admin/ to /control/ answer: control", 'really-simple-ssl' ),
            'label'    => __( "Custom login URL", 'really-simple-ssl' ),
            'disabled' => false,
            'default'  => false,
            'condition_action'   => 'hide',
            'react_conditions' => [
                'relation' => 'AND',
                [
                    'change_login_url_enabled' => 1,
                ]
            ],
        ],
        [
            'id'       => 'change_login_url_failure_url',
            'menu_id'  => 'hardening',
            'group_id' => 'hardening_extended',
            'type'     => 'postdropdown',
            'tooltip'  => __( "Users trying to enter via /wp-admin or /wp-login.php will be redirected to this URL.", 'really-simple-ssl' ),
            'label'    => '',
            'disabled' => false,
            'default'  => '404_default',
            'condition_action'   => 'hide',
            'react_conditions' => [
                'relation' => 'AND',
                [
                    'change_login_url_enabled' => 1,
                ]
            ],
        ],
		[
			'id'       => 'xmlrpc_status',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_xml',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'xmlrpc_status_lm_enabled_once',
			'menu_id'  => 'hardening',
			'group_id' => 'hardening_xml',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'               => 'xmlrpc_allow_list',
			'control_field'    => 'xmlrpc_status',
			'menu_id'          => 'hardening',
			'group_id'         => 'hardening_xml',
			'type'             => 'learningmode',
			'label'            => __( "XML-RPC", 'really-simple-ssl' ),
			'disabled'         => false,
			'default'          => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'disable_xmlrpc' => false,
				]
			],
			'columns'          => [
				[
					'name'     => __( 'Method', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'method',
				],
				[
					'name'     => __( 'Login status', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'login_statusControl',
				],
				[
					'name'     => __( 'Count', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'count',
				],
				[
					'name'     => __( 'Action', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'statusControl',
				],
				[
					'name'     => __( 'Delete', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'deleteControl',
				],
			],
		],
		[
			'id'       => 'x_xss_protection',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'label'    => __( "X-XSS-Protection", "really-simple-ssl-pro" ),
			'options' => [
				'disabled'       =>  __("disabled", "really-simple-ssl" ),
				'zero'       =>  "0 ".__("(recommended)", "really-simple-ssl" ),
				'one'        => "1",
				'mode_block' => "1; mode=block",
			],
			'disabled' => false,
			'default'  => 'zero',
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/about-recommended-security-headers/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About Recommended Security Headers", 'really-simple-ssl' ),
				'text'  => __( 'These security headers are the fundamental security measures to protect your website visitors while visiting your website.', 'really-simple-ssl' ),
			],
		],
		[
			'id'       => 'x_content_type_options',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'checkbox',
			'label'    => __( "X-Content-Type options", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'x_frame_options',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'options'  => [
				'disabled'   => __( "Off", "really-simple-ssl" ),
				'DENY'       => 'DENY',
				'SAMEORIGIN' => 'SAMEORIGIN',
			],
			'label'    => __( "X-Frame options", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'referrer_policy',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'options'  => [
				'disabled'                        => __( "Off", "really-simple-ssl" ),
				'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin'.' ('.__("recommended","really-simple-ssl").')',
				'no-referrer'                     => 'no-referrer',
				'origin'                          => 'origin',
				'no-referrer-when-downgrade'      => 'no-referrer-when-downgrade',
				'unsafe-url'                      => 'unsafe-url',
				'origin-when-cross-origin'        => 'origin-when-cross-origin',
				'strict-origin'                   => 'strict-origin',
				'same-origin'                     => 'same-origin',
			],
			'label'    => __( "Referrer Policy", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => 'strict-origin-when-cross-origin',
		],
		[
			'id'       => 'hsts',
			'menu_id'  => 'hsts',
			'group_id' => 'hsts',
			'type'     => 'checkbox',
			'label'    => __( "HTTP Strict Transport Security", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-hsts/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About HTTP Strict Transport Security", 'really-simple-ssl' ),
				'text'  => __( 'Leveraging your SSL certificate with HSTS is a staple for every website. Force your website over SSL, mitigating risks of malicious counterfeit websites in your name.',
					'really-simple-ssl' ),
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'ssl_enabled' => '1',
				]
			],
		],
		[
			'id'               => 'hsts_preload',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'checkbox',
			'label'            => __( "Include preload", "really-simple-ssl-pro" ),
			'comment'          => sprintf(__( "After enabling this feature, you can submit your site to %shstspreload.org%s", "really-simple-ssl-pro" ),'<a target="_blank" href="https://hstspreload.org?domain='.site_url().'">',"</a>"),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'configure_on_activation' => [
				'condition' => 1,
				[
					'hsts_subdomains' => true,
					'hsts_max_age' => 63072000,
				]
			],
			'disabled'         => false,
			'default'          => false,
		],
		[
			'id'               => 'hsts_subdomains',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'checkbox',
			'label'            => __( "Include subdomains", "really-simple-ssl-pro" ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'disabled'         => false,
			'default'          => false,
		],
		[
			'id'               => 'hsts_max_age',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'select',
			'options'          => [
				'86400'    => __( 'One day (for testing only)', 'really-simple-ssl' ),
				'31536000' => __( 'One year', 'really-simple-ssl' ),
				'63072000' => __( 'Two years (required for preload)', 'really-simple-ssl' ),
			],
			'label'            => __( "Choose the max-age for HSTS", "really-simple-ssl-pro" ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'disabled'         => false,
			'default'          => '63072000',
		],
		[
			'id'       => 'cross_origin_opener_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'                 => __( 'Off', 'really-simple-ssl' ),
				'unsafe-none'              => 'unsafe-none',
				'same-origin-allow-popups' => 'same-origin-allow-popups',
				'same-origin'              => 'same-origin',
			],
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-cross-origin-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About Cross Origin Policies", 'really-simple-ssl' ),
				'text'  => __( 'One of the most powerful features, and therefore the most complex are the Cross-Origin headers that can isolate your website so any data leaks are minimized.',
					'really-simple-ssl' ),
			],
			'label'    => __( "Cross Origin Opener Policy", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'cross_origin_resource_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'     => __( 'Off', 'really-simple-ssl' ),
				'same-site'    => 'same-site',
				'same-origin'  => 'same-origin',
				'cross-origin' => 'cross-origin',
			],
			'label'    => __( "Cross Origin Resource Policy", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'cross_origin_embedder_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'     => __( 'Off', 'really-simple-ssl' ),
				'require-corp' => 'require-corp',
				'same-origin'  => 'same-origin',
				'unsafe-none'  => 'unsafe-none',
			],
			'label'    => __( "Cross Origin Embedder Policy", "really-simple-ssl-pro" ),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'          => 'mixedcontentscan',
			'menu_id'     => 'mixed_content_scan',
			'group_id'    => 'mixedcontentscan',
			'type'        => 'mixedcontentscan',
			'label'       => __( "Mixed content scan", "really-simple-ssl-pro" ),
			'help'        => [
				'label' => 'default',
				'url' => 'https://really-simple-ssl.com/definition/what-is-mixed-content/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Mixed Content Scan", 'really-simple-ssl' ),
				'text'  => __( 'The extensive mixed content scan will list all current and future issues and provide a fix, or instructions to fix manually.', 'really-simple-ssl' ),
			],
			'columns'     => [
				[
					'name'     => __( 'Type', 'really-simple-ssl' ),
					'sortable' => true,
					'column'   => 'warningControl',
					'grow'     => 0,
				],
				[
					'name'     => __( 'Description', 'really-simple-ssl' ),
					'sortable' => true,
					'column'   => 'description',
					'grow'     => 10,
				],
				[
					'name'     => __( 'Location', 'really-simple-ssl' ),
					'sortable' => true,
					'column'   => 'locationControl',
					'grow'     => 4,
				],

				[
					'name'     => __( 'Details', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'detailsControl',
					'grow'     => 0,
				],
				[
					'name'     => __( 'Fix', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'fixControl',
					'grow'     => 0,
					'right'    => true,
				],
			],
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'       => 'permissions_policy',
			'menu_id'  => 'permissions_policy',
			'group_id' => 'permissions_policy',
			'type'     => 'permissionspolicy',
			'options'  => [ '*' => __( "Allow", "really-simple-ssl" ), '()' => __( "Disable", "really-simple-ssl" ), 'self' => __( "Self (Default)", "really-simple-ssl" ) ],
			'label'    => __( "Permissions Policy", 'really-simple-ssl' ),
			'disabled' => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-permissions-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Permission Policy", 'really-simple-ssl' ),
				'text'  => __( 'Browser features are plentiful, but most are not needed on your website.', 'really-simple-ssl' ).' '.__('They might be misused if you don’t actively tell the browser to disable these features.', 'really-simple-ssl' ),
			],
			'columns'  => [
				[
					'name'     => __( 'Feature', 'really-simple-ssl' ),
					'sortable' => true,
					'column'   => 'title',
				],
				[
					'name'     => __( 'Options', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'valueControl',
				],
			],
			'default'  => [
				[
					'id'     => 'accelerometer',
					'title'  => 'Accelerometer',
					'value'  => 'self',
					'status' => true,
				],
				[
					'id'     => 'autoplay',
					'title'  => 'Autoplay',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'camera',
					'title'  => 'Camera',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'encrypted-media',
					'title'  => 'Encrypted Media',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'fullscreen',
					'title'  => 'Fullscreen',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'geolocation',
					'title'  => 'Geolocation',
					'value'  => '*',
					'status' => false,
				],
				[
					'id'     => 'microphone',
					'title'  => 'Microphone',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'midi',
					'title'  => 'Midi',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'payment',
					'title'  => 'Payment',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'display-capture',
					'title'  => 'Display Capture',
					'value'  => 'self',
					'status' => false,
				],
			],
		],
		[
			'id'       => 'enable_permissions_policy',
			'menu_id'  => 'permissions_policy',
			'group_id' => 'permissions_policy',
			'type'     => 'hidden',
			'label'    => __( "Enable Permissions Policy", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'upgrade_insecure_requests',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'upgrade_insecure_requests',
			'type'     => 'checkbox',
			'label'    => __( "Serve encrypted and authenticated responses", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-content-security-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Content Security Policy", 'really-simple-ssl' ),
				'text'  => __( 'The content security policy has many options, so we always recommend starting in ‘learning mode’ to see what files and scripts are loaded.', 'really-simple-ssl' ),
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'ssl_enabled' => '1',
				]
			],
		],
		[
			'id'       => 'csp_frame_ancestors',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'frame_ancestors',
			'type'     => 'select',
			'options'  => [
				'disabled' => __( "Disable (Default)", "really-simple-ssl" ),
				'none'     => "None",
				'self'     => "Self",
			],
			'label'    => __( "Allow your domain to be embedded", "really-simple-ssl" ),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'csp_frame_ancestors_urls',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'frame_ancestors',
			'type'     => 'textarea',
			'label'    => __( "Add additional domains which can embed your website, if needed. Comma seperated.", "really-simple-ssl" ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'csp_status',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'content_security_policy',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'csp_status_lm_enabled_once',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'content_security_policy',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'            => 'content_security_policy',
			'control_field' => 'csp_status',
			'menu_id'       => 'content_security_policy',
			'group_id'      => 'content_security_policy',
			'type'          => 'learningmode',
			'label'         => "Content Security Policy",
			'disabled'      => false,
			'default'       => false,
			'columns'       => [
				[
					'name'     => __( 'Location', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'documenturi',
				],
				[
					'name'     => __( 'Directive', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'violateddirective',
				],
				[
					'name'     => __( 'Source', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'blockeduri',
				],
				[
					'name'     => __( 'Action', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'statusControl',
				],
				[
					'name'     => '',//__('Delete', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'deleteControl',
				],
			],
		],
	];

	$fields = apply_filters( 'rsssl_fields', $fields );
	foreach ( $fields as $key => $field ) {
		$field = wp_parse_args( $field, [ 'default' => '', 'id' => false, 'visible' => true, 'disabled' => false, 'new_features_block' => false ] );
		//handle server side conditions
		//but not if outside our settings pages
		if ( rsssl_is_logged_in_rest() && isset( $field['server_conditions'] ) ) {
			if ( ! rsssl_conditions_apply( $field['server_conditions'] ) ) {
				unset( $fields[ $key ] );
				continue;
			}
		}
		if ( $load_values ) {
			$value          = rsssl_sanitize_field( rsssl_get_option( $field['id'], $field['default'] ), $field['type'], $field['id'] );
			$field['value'] = apply_filters( 'rsssl_field_value_' . $field['id'], $value, $field );
			$fields[ $key ] = apply_filters( 'rsssl_field', $field, $field['id'] );
		}
	}

	$fields = apply_filters( 'rsssl_fields_values', $fields );

	return array_values( $fields );
}

function rsssl_blocks() {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$blocks = [
		[
			'id'       => 'progress',
			'title'    => __( "Progress", 'really-simple-ssl' ),
			'controls' => [
				'type' => 'react',
				'data' => 'ProgressHeader'
			],
			'content'  => [ 'type' => 'react', 'data' => 'ProgressBlock' ],
			'footer'   => [ 'type' => 'react', 'data' => 'ProgressFooter' ],
			'class'    => ' rsssl-column-2',
		],
		[
			'id'       => 'ssllabs',
			'controls' => [
				'type' => 'html',
				'data' => __( "Powered by Qualys", 'really-simple-ssl' ),
			],
			'title'    => __( "Status", 'really-simple-ssl' ),
			'content'  => [ 'type' => 'react', 'data' => 'SslLabs' ],
			'footer'   => [ 'type' => 'react', 'data' => 'SslLabsFooter' ],
			'class'    => '',
		],
		[
			'id'       => 'new-features-block',
			'controls' => false,
			'title'    => __( "Hardening", 'really-simple-ssl' ),
			'content'  => [ 'type' => 'react', 'data' => 'SecurityFeaturesBlock' ],
			'footer'   => [ 'type' => 'react', 'data' => 'SecurityFeaturesFooter' ],
			'class'    => '',
		],
		[
			'id'       => 'tips_tricks',
			'controls' => false,
			'title'    => __( "Tips & Tricks", 'really-simple-ssl' ),
			'content'  => [ 'type' => 'template', 'data' => 'tips-tricks.php' ],
			'footer'   => [ 'type' => 'template', 'data' => 'tips-tricks-footer.php' ],
			'class'    => ' rsssl-column-2',
		],
		[
			'id'       => 'other-plugins',
			'controls' => [ 'type' => 'html',
			                'data' => '<a class="rsp-logo" href="https://really-simple-plugins.com/"><img src="' . rsssl_url
			                          . 'assets/img/really-simple-plugins.svg" alt="Really Simple Plugins" /></a>'
			],
			'title'    => __( "Other Plugins", 'really-simple-ssl' ),
			'content'  => [ 'type' => 'react', 'data' => 'OtherPlugins' ],
			'footer'   => [ 'type' => 'html', 'data' => '' ],
			'class'    => ' rsssl-column-2 no-border no-background',
		],
	];

	$blocks = apply_filters( 'rsssl_blocks', $blocks );
	foreach ( $blocks as $index => $block ) {
		if ( $block['content']['type'] === 'template' ) {
			$template                            = $block['content']['data'];
			$blocks[ $index ]['content']['type'] = 'html';
			$blocks[ $index ]['content']['data'] = rsssl_get_template( $template );
		}
		if ( $block['footer']['type'] === 'template' ) {
			$template                           = $block['footer']['data'];
			$blocks[ $index ]['footer']['type'] = 'html';
			$blocks[ $index ]['footer']['data'] = rsssl_get_template( $template );
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

function rsssl_get_template( $template ) {
	if ( ! rsssl_user_can_manage() ) {
		return '';
	}
	$html = '';
	$file = trailingslashit( rsssl_path ) . 'settings/templates/' . $template;
	if ( file_exists( $file ) ) {
		ob_start();
		require $file;
		$html = ob_get_clean();
	}

	return $html;
}
