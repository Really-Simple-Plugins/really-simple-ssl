<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'               => 'letsencrypt',
				'menu_id'          => 'encryption_lets_encrypt',
				'group_id'         => 'encryption_lets_encrypt',
				'type'             => 'lets-encrypt',
				'default'          => false,
				'server_conditions'    => [
					'relation' => 'AND',
					[
						'rsssl_letsencrypt_generation_allowed' => true,
					]
				],
			],
		]
	);
}, 200 );
