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
				'id'       => 'two_fa_forced_roles',
				'forced_roles_id'         => 'two_fa_forced_roles',
				'menu_id'  => 'two-fa',
				'group_id' => 'two_fa_general',
				'type'     => 'two_fa_roles',
				'default'  => [],
				'label'    => __( 'Enforce for:', 'really-simple-ssl' ),
				'tooltip'  => __( 'Enforcing 2FA ensures that all users with the selected roles must login using Two-Factor Authentication. It is strongly recommended to at least enforce 2FA for Administrators.', 'really-simple-ssl' ),
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
				'tooltip'  => __( 'During the grace period users can configure their Two-Factor method. When the grace period ends, users for which 2FA is enforced won’t be able to login unless 2FA is correctly configured. The grace period is also applied to new users.', 'really-simple-ssl' ),
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
				'default'  => [],
				'label'    => __( 'Enable for:', 'really-simple-ssl' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'login_protection_enabled' => 1
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
				'group_id' => 'two_fa_totp',
				'type'     => 'roles_enabled_dropdown',
                'premium'   => true,
				'default'  => [],
				'label'    => __( 'Enable for:', 'really-simple-ssl' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'login_protection_enabled' => true
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
				'columns' => [
					[
						'name'     => __( 'Username', 'really-simple-ssl' ),
						'sortable' => true,
						'searchable' => true,
						'visible' => true,
						'column'   => 'user',
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
						'name'     => __( 'Method', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => true,
						'visible' => true,
						'width'     => '20%',
						'column'   => 'rsssl_two_fa_providers',
					],
					[
						'name'     => __( 'Status', 'really-simple-ssl' ),
						'sortable' => false,
						'searchable' => false,
						'visible' => true,
						'width'     => '20%',
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
