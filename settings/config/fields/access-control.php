<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'       => 'enforce_password_security_enabled',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'checkbox',
				'label'    => __( "Enforce strong passwords", "really-simple-ssl" ),
				'disabled' => false,
				'default'  => 'disabled',
				'tooltip'  => __( "This adds extra requirements for strong passwords for new users and updated passwords.", 'really-simple-ssl' ),
				'help'     => [
					'label' => 'default',
					'url'   => 'instructions/password-security',
					'title' => __( "Enforce strong passwords", 'really-simple-ssl' ),
					'text'  => __( 'Improve the default WordPress password strength check. You can also enforce frequent password changes for user roles.', 'really-simple-ssl' ).' '.__('They might be misused if you don’t actively tell the browser to disable these features.', 'really-simple-ssl' ),
				],
			],
			[
				'id'       => 'enforce_frequent_password_change',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'checkbox',
				'label'    => __( "Enforce frequent password change", "really-simple-ssl" ),
				'disabled' => false,
				'default'  => 'disabled',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enforce_password_security_enabled' => 1,
					]
				],
			],
			[
				'id'       => 'password_change_roles',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'roles_dropdown',
				'default'  => [ 'administrator'],
				'label'    => __( "User roles for password change", "really-simple-ssl" ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enforce_frequent_password_change' => 1,
					]
				],
			],
			[
				'id'       => 'password_change_frequency',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'select',
				'default'  => '12',
				'options'   => [
					'6' => __( "6 months", "really-simple-ssl" ),
					'12' => __( "1 year", "really-simple-ssl" ),
					'24' => __( "2 years", "really-simple-ssl" ),
				],
				'label'    => __( "Change passwords every", "really-simple-ssl" ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enforce_frequent_password_change' => 1,
					]
				],
			],
			[
				'id'       => 'login_cookie_expiration',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'select',
				'default'  => '48',
				'options'   => [
					'8' => __( "8 hours (recommended)", "really-simple-ssl" ),
					'48' => __( "48 hours (default)", "really-simple-ssl" ),
				],
				'label'    => __( "Limit logged in session duration", "really-simple-ssl" ),
				'help'             => [
					'label' => 'default',
					'title' => __( "Prevent session hijacking", 'really-simple-ssl' ),
					'text'  => __( "Really Simple Security allows you to limit the default logged in session duration. By default, WordPress will keep users logged in for 48 hours, or even 14 days when clicking the ‘remember me’ checkbox. An attacker could possibly steal the logged in cookie and gain access to a user’s account. Limiting the logged in duration to 8 hours will greatly reduce the risk of session hijacking.", 'really-simple-ssl' ),
				],
			],
			[
				'id'       => 'hide_rememberme',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'checkbox',
				'default'  => false,
				'label'    => __( "Hide the remember me checkbox", "really-simple-ssl" ),
			],
		]
	);
}, 200 );
