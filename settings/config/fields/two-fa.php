<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id'       => 'login_protection_enabled',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_general',
				'type'     => 'checkbox',
				'label'    => __( "Enable login protection", "really-simple-ssl" ),
				'help'     => [
					'label' => 'default',
					'url'   => 'instructions/about-login-protection',
					'title' => __("About Login Protection", 'really-simple-ssl'),
					'text'  => __('Two-step verification is the first feature we regard as login protection. Want to know more about this feature, and what is to come?',
						'really-simple-ssl'),
				],
				'disabled' => false,
				'default'  => 'disabled',
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'       => 'two_fa_enabled',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_email',
				'type'     => 'checkbox',
				'label'    => __( "Enable two-step verification", "really-simple-ssl" ),
				'tooltip'  => __( "By enabling this feature you understand that email validation is required, and you can send email from your server reliably.", 'really-simple-ssl' ),
				'disabled' => false,
				'warning'  => true,
				'default'  => 'disabled',
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'       => 'two_fa_optional_roles',
				'forced_roles_id'         => 'two_fa_forced_roles',
				'optional_roles_id' 	   => 'two_fa_optional_roles',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_email',
				'type'     => 'two_fa_roles',
				'default'  => [ 'editor', 'author', 'contributor', 'administrator' ],
				'label'    => __( "Optional for:", "really-simple-ssl" ),
				'tooltip'  => __( "Two-step verification will be optional for these user roles, and they can disable it on first login.", 'really-simple-ssl' ),
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],

			[
				'id'       => 'two_fa_forced_roles',
				'forced_roles_id'         => 'two_fa_forced_roles',
				'optional_roles_id' 	   => 'two_fa_optional_roles',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_email',
				'type'     => 'two_fa_roles',
				'default'  => [],
				'label'    => __( "Force on:", "really-simple-ssl" ),
				'tooltip'  => __( "These user roles are forced to enter the authentication code.", 'really-simple-ssl' ),
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'    => 'two_fa_users_table',
				'menu_id' => 'two-fa',
				'group_id' => 'two_fa_users',
				'type' => 'twofa-datatable',
				'action' => 'two_fa_table',
				'label' => __('Users', 'really-simple-ssl'),
				'disabled' => false,
				'default' => false,
				'columns' => [
					[
						'name'     => __( 'Username', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => true,
						'visible' => true,
						'column'   => 'user',
						'width'    => '20%',
					],
					[
						'name'     => __( 'Status', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => false,
						'visible' => true,
						'column'   => 'status_for_user',
						'width'    => '20%',
					],
					[
						'name'     => __( 'User role', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => false,
						'visible' => true,
						'column'   => 'user_role',
						'width'    => '20%',
					],
					[
						'name'     => __( '', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => false,
						'visible' => true,
						'width'     => '40%',
						'column'   => 'resetControl',
					],
				],
			],
		]
	);
}, 200 );
