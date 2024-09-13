<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id'               => 'redirect',
				'menu_id'          => 'encryption',
				'group_id'         => 'encryption_redirect',
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
					'title'     => __("Settings update: .htaccess redirect", 'really-simple-ssl'),
					'message'   => __("The .htaccess redirect has been enabled on your site. If the server configuration is non-standard, this might cause issues. Please check if all pages on your site are functioning properly.",
						'really-simple-ssl'),
					'url'       => 'https://really-simple-ssl.com/remove-htaccess-redirect-site-lockout',
					'condition' => ['redirect' => 'htaccess']
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
				'menu_id'  => 'encryption',
				'group_id' => 'mixed-content-general',
				'type'     => 'checkbox',
				'label'    => __("Mixed content fixer", 'really-simple-ssl'),
				'disabled' => false,
				'default'  => true,
			],
			[
				'id'               => 'switch_mixed_content_fixer_hook',
				'menu_id'  => 'encryption',
				'group_id' => 'mixed-content-general',
				'type'             => 'checkbox',
				'label'            => __("Mixed content fixer - init hook", 'really-simple-ssl'),
				'disabled'         => false,
				'required'         => false,
				'default'          => false,
				'tooltip'          => __('If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.',
					'really-simple-ssl'),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'mixed_content_fixer' => 1,
					]
				],
			],
			[
				'id'       => 'admin_mixed_content_fixer',
				'menu_id'  => 'encryption',
				'group_id' => 'mixed-content-general',
				'type'     => 'checkbox',
				'label'    => __("Mixed content fixer - back-end", "really-simple-ssl"),
				'tooltip'  => __("Only enable this if you experience mixed content in the admin environment of your WordPress website.",
					'really-simple-ssl'),
				'disabled' => false,
				'default'  => false,
			],

			[
				'id'          => 'mixedcontentscan',
				'menu_id'     => 'encryption',
				'group_id'    => 'mixed-content-scan',
				'type'        => 'mixedcontentscan',
				'label'       => __( "Mixed content scan", "really-simple-ssl" ),
				'help'        => [
					'label' => 'default',
					'url' => 'definition/what-is-mixed-content',
					'title' => __( "About the Mixed Content Scan", 'really-simple-ssl' ),
					'text'  => __( 'The extensive mixed content scan will list all issues and provide a fix, or instructions to fix manually.', 'really-simple-ssl' ),
				],
				'columns'     => [
					[
						'name'     => __( 'Type', 'really-simple-ssl' ),
						'sortable' => true,
						'column'   => 'warningControl',
						'grow'     => 5,
						'width'   => '5%',
					],
					[
						'name'     => __( 'Description', 'really-simple-ssl' ),
						'sortable' => true,
						'column'   => 'description',
						'grow'     => 15,
					],
					[
						'name'     => __( 'Location', 'really-simple-ssl' ),
						'sortable' => true,
						'column'   => 'locationControl',
						'grow'     => 4,
					],

					[
						'name'     => __( '', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'detailsControl',
						'grow'     => 5,
					],
					[
						'name'     => __( '', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'fixControl',
						'grow'     => 5,
						'right'    => true,
					],
				],
				'disabled'    => false,
				'default'     => false,
			],
		]
	);
}, 300 );
