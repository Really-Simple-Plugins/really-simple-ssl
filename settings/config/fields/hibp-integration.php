<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function ( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'       => 'enable_hibp_check',
				'menu_id'  => 'password_security',
				'group_id' => 'password_security_passwords',
				'type'     => 'checkbox',
				'label'    => __( 'Enable compromised password check', 'really-simple-ssl' ),
				'tooltip'  => __( "Prevent usage of passwords that have been included in a databreach. This securely verifies part of the hashed password via the Have I Been Pwned API.", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
				'warning'  => false,
			],
		]
	);
}, 200 );