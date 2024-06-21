<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'       => 'x_xss_protection',
				'menu_id'  => 'recommended_security_headers',
				'group_id' => 'recommended_security_headers',
				'type'     => 'select',
				'label'    => "X-XSS-Protection",
				'options'  => [
					'disabled'   => __("Disabled", "really-simple-ssl"),
					'zero'       => "0 ".__("(recommended)", "really-simple-ssl"),
					'one'        => "1",
					'mode_block' => "1; mode=block",
				],
				'disabled' => false,
				'default'  => 'zero',
				'help'     => [
					'label' => 'default',
					'url'   => 'definition/about-recommended-security-headers',
					'title' => __("About Recommended Security Headers", 'really-simple-ssl'),
					'text'  => __('These security headers are the fundamental security measures to protect your website visitors while visiting your website.',
						'really-simple-ssl'),
				],
			],
			[
				'id'       => 'x_content_type_options',
				'menu_id'  => 'recommended_security_headers',
				'group_id' => 'recommended_security_headers',
				'type'     => 'checkbox',
				'label'    => "X-Content-Type options",
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'x_frame_options',
				'menu_id'  => 'recommended_security_headers',
				'group_id' => 'recommended_security_headers',
				'type'     => 'select',
				'options'  => [
					'disabled'   => __("Off", "really-simple-ssl"),
					'DENY'       => 'DENY',
					'SAMEORIGIN' => 'SAMEORIGIN',
				],
				'label'    => "X-Frame options",
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'referrer_policy',
				'menu_id'  => 'recommended_security_headers',
				'group_id' => 'recommended_security_headers',
				'type'     => 'select',
				'options'  => [
					'disabled'                        => __("Off", "really-simple-ssl"),
					'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin'.' ('.__("recommended",
							"really-simple-ssl").')',
					'no-referrer'                     => 'no-referrer',
					'origin'                          => 'origin',
					'no-referrer-when-downgrade'      => 'no-referrer-when-downgrade',
					'unsafe-url'                      => 'unsafe-url',
					'origin-when-cross-origin'        => 'origin-when-cross-origin',
					'strict-origin'                   => 'strict-origin',
					'same-origin'                     => 'same-origin',
				],
				'label'    => "Referrer Policy",
				'disabled' => false,
				'default'  => 'strict-origin-when-cross-origin',
			],
			[
				'id'               => 'hsts',
				'menu_id'          => 'hsts',
				'group_id'         => 'hsts',
				'type'             => 'checkbox',
				'label'            => __("HTTP Strict Transport Security", "really-simple-ssl"),
				'disabled'         => false,
				'default'          => false,
				'help'             => [
					'label' => 'default',
					'url'   => 'definition/what-is-hsts/',
					'title' => __("About HTTP Strict Transport Security", 'really-simple-ssl'),
					'text'  => __('Leveraging your SSL certificate with HSTS is a staple for every website. Force your website over SSL, mitigating risks of malicious counterfeit websites in your name.',
						'really-simple-ssl'),
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'ssl_enabled' => '1',
					]
				],
			],
			[
				'id'                      => 'hsts_preload',
				'menu_id'                 => 'hsts',
				'group_id'                => 'hsts',
				'type'                    => 'checkbox',
				'label'                   => __("Include preload", "really-simple-ssl"),
				'comment'                 => sprintf(__("After enabling this feature, you can submit your site to %shstspreload.org%s",
					"really-simple-ssl"), '<a target="_blank" href="https://hstspreload.org?domain='.site_url().'">',
					"</a>"),
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'hsts' => true,
					]
				],
				'configure_on_activation' => [
					'condition' => 1,
					[
						'hsts_subdomains' => true,
						'hsts_max_age'    => 63072000,
					]
				],
				'disabled'                => false,
				'default'                 => false,
			],
			[
				'id'               => 'hsts_subdomains',
				'menu_id'          => 'hsts',
				'group_id'         => 'hsts',
				'type'             => 'checkbox',
				'label'            => __("Include subdomains", "really-simple-ssl"),
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
					'86400'    => __('One day (for testing only)', 'really-simple-ssl'),
					'31536000' => __('One year', 'really-simple-ssl'),
					'63072000' => __('Two years (required for preload)', 'really-simple-ssl'),
				],
				'label'            => __("Choose the max-age for HSTS", "really-simple-ssl"),
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
					'disabled'                 => __('Off', 'really-simple-ssl'),
					'unsafe-none'              => 'unsafe-none',
					'same-origin-allow-popups' => 'same-origin-allow-popups',
					'same-origin'              => 'same-origin',
				],
				'help'     => [
					'label' => 'default',
					'url'   => 'definition/what-is-a-cross-origin-policy/',
					'title' => __("About Cross Origin Policies", 'really-simple-ssl'),
					'text'  => __('One of the most powerful features, and therefore the most complex are the Cross-Origin headers that can isolate your website so any data leaks are minimized.',
						'really-simple-ssl'),
				],
				'label'    => __("Cross Origin Opener Policy", "really-simple-ssl"),
				'disabled' => false,
				'default'  => 'disabled',
			],
			[
				'id'       => 'cross_origin_resource_policy',
				'menu_id'  => 'cross_origin_policy',
				'group_id' => 'cross_origin_policy',
				'type'     => 'select',
				'options'  => [
					'disabled'     => __('Off', 'really-simple-ssl'),
					'same-site'    => 'same-site',
					'same-origin'  => 'same-origin',
					'cross-origin' => 'cross-origin',
				],
				'label'    => __("Cross Origin Resource Policy", "really-simple-ssl"),
				'disabled' => false,
				'default'  => 'disabled',
			],
			[
				'id'       => 'cross_origin_embedder_policy',
				'menu_id'  => 'cross_origin_policy',
				'group_id' => 'cross_origin_policy',
				'type'     => 'select',
				'options'  => [
					'disabled'     => __('Off', 'really-simple-ssl'),
					'require-corp' => 'require-corp',
					'same-origin'  => 'same-origin',
					'unsafe-none'  => 'unsafe-none',
				],
				'label'    => __("Cross Origin Embedder Policy", "really-simple-ssl"),
				'disabled' => false,
				'default'  => 'disabled',
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
					'url'   => 'definition/what-is-a-permissions-policy',
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
						'name'     => __( '', 'really-simple-ssl' ),
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
					'url'   => 'definition/what-is-a-content-security-policy',
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
					'disabled' => __("Yes (don't set header)", "really-simple-ssl"),
					'none'     => "None",
					'self'     => __("Self (Default)", "really-simple-ssl"),
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
				'disabled' => maybe_disable_frame_ancestors_url_field(),
				'default'  => false,
				'react_conditions'        => [
					'relation' => 'AND',
					[
						'csp_frame_ancestors' => 'NOT disabled',
					]
				],
			],
			[
				'id'       => 'csp_status',
				'menu_id'  => 'content_security_policy',
				'group_id' => 'content_security_policy_source_directives',
				'type'     => 'hidden',
				'label'    => '',
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'csp_status_lm_enabled_once',
				'menu_id'  => 'content_security_policy',
				'group_id' => 'content_security_policy_source_directives',
				'type'     => 'hidden',
				'label'    => '',
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'            => 'content_security_policy_source_directives',
				'control_field' => 'csp_status',
				'menu_id'       => 'content_security_policy',
				'group_id'      => 'content_security_policy_source_directives',
				'type'          => 'learningmode',
				'label'         => "Content Security Policy",
				'disabled'      => false,
				'default'       => false,
				'columns'       => [
					[
						'name'     => __( 'Location', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'documenturi',
						'grow'     => 2,
					],
					[
						'name'     => __( 'Directive', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'violateddirective',
						'grow'     => 1,
					],
					[
						'name'     => __( 'Source', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'blockeduri',
						'grow'     => 1,
					],
					[
						'name'     => __( 'Action', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'statusControl',
						'grow'     => 1,
					],
					[
						'name'     => __('Delete', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'deleteControl',
						'grow'     => 1,
					],
					[   //placeholder until we have resolved the columns
						'name'     => '',
					],
				],
			],
		]
	);
}, 200 );

