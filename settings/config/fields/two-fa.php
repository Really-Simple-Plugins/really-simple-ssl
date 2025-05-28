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
				'label'    => __( "Enable Two-Factor Authentication", "really-simple-ssl" ),
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
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_general',
				'type'     => 'two_fa_roles',
				'default'  => [],
				'label'    => __( 'Enforce secure authentication for:', 'really-simple-ssl' ),
				'tooltip'  => __( 'These user roles will be enforced to either configure Two-factor Authentication or Passkey log in. We recommend to enforce at least administrators.', 'really-simple-ssl' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'login_protection_enabled' => true,
					]
				],
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'       => 'enable_passkey_login',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_general',
				'type'     => 'checkbox',
				'premium'  => true,
				'upgrade'  => 'https://really-simple-ssl.com/login-protection/',
				'label'    => __( "Allow secure log in with Passkeys", "really-simple-ssl" ),
				'disabled' => false,
				'tooltip'  => __('Passkeys are a very secure and convenient way to log in. It allows the user to authenticate using their device, browser or password manager.', 'really-simple-ssl'),
				'default'  => 'disabled',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'login_protection_enabled' => true,
					]
				],
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'       => 'two_fa_grace_period',
				'menu_id'  => 'two_fa',
				'group_id' => 'two_fa_general',
				'type'     => 'select',
				'label'    => __( 'Allow grace period', 'really-simple-ssl' ),
				'tooltip'  => __( 'During the grace period users can configure their secure authentication method. When the grace period ends, users for which secure authentication is enforced won’t be able to log in unless secure authentication is correctly configured. The grace period is also applied to new users.', 'really-simple-ssl' ),
				'disabled' => false,
				'options'          => [
					'1'   => sprintf(__('%s day', 'really-simple-ssl'), 1),
					'5'   => sprintf(__('%s days', 'really-simple-ssl'), 5),
					'10'   => sprintf(__('%s days', 'really-simple-ssl'), 10),
					'30' => sprintf(__('%s days', 'really-simple-ssl'), 30),
				],
				'warning'  => false,
				'default'  => '10',
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'login_protection_enabled' => true,
					]
				],
			],
			[
				'id'       => 'two_fa_enabled_roles_email',
				'enabled_roles_id'         => 'two_fa_forced_roles',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_email',
				'type'     => 'roles_enabled_dropdown',
                'disabled' => (rsssl_is_email_verified() === false),
                'disabledTooltipText' => __("This feature is disabled because you have not verified that e-mail is correctly configured on your site.", "really-simple-ssl"),
				'default'  => [],
                'tooltip'  => __('Email log in will send an authentication code to the user’s email address. This is considered less secure than other 2FA methods.', 'really-simple-ssl'),
				'label'    => __( 'Enable Email Authentication for:', 'really-simple-ssl' ),
                'react_conditions' => [
                    'relation' => 'AND',
                    [
                        'login_protection_enabled' => 1,
                    ]
                ],
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
			],
			[
				'id'       => 'two_fa_enabled_roles_totp',
				'enabled_roles_id'         => 'two_fa_forced_roles',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_email',
				'type'     => 'roles_enabled_dropdown',
                'premium'   => true,
				'upgrade'  => 'https://really-simple-ssl.com/login-protection/',
				'default'  => ['administrator'],
                'tooltip'  => __('TOTP means authentication using apps like Google Authenticator.', 'really-simple-ssl'),
				'label'    => __( 'Enable TOTP Authentication for:', 'really-simple-ssl' ),
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
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'is_multisite' => false,
					]
				],
                'roles_filter' => true,
				'columns' => [
					[
						'name'     => __( 'Username', 'really-simple-ssl' ),
						'sortable' => true,
						'searchable' => true,
						'visible' => true,
						'column'   => 'user',
						'width'    => '70%',
					],
//					[
//						'name'     => __( 'User role', 'really-simple-ssl' ),
//						'sortable' => false,
//						'searchable' => false,
//						'visible' => true,
//						'column'   => 'user_role',
//						'width'    => '20%',
//					],
					[
						'name'     => __( 'Method', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => true,
						'visible' => true,
						'width'     => '10%',
						'column'   => 'rsssl_two_fa_providers',
					],
					[
						'name'     => __( 'Status', 'really-simple-ssl' ),
						'sortable' => true,
						'searchable' => false,
						'visible' => true,
						'width'     => '10%',
						'column'   => 'status_for_user',
					],
					[
						'name'     => '',
						'sortable' => false,
						'searchable' => false,
						'visible' => true,
						'column'   => 'resetControl',
					],
				],
			],
		]
	);
}, 200 );
