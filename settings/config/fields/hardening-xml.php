<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'       => 'xmlrpc_status',
				'menu_id'  => 'hardening-xml',
				'group_id' => 'hardening-xml',
				'type'     => 'hidden',
				'label'    => '',
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'xmlrpc_status_lm_enabled_once',
				'menu_id'  => 'hardening-xml',
				'group_id' => 'hardening-xml',
				'type'     => 'hidden',
				'label'    => '',
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'               => 'xmlrpc_allow_list',
				'control_field'    => 'xmlrpc_status',
				'menu_id'          => 'hardening-extended',
				'group_id'         => 'hardening-xml',
				'type'             => 'learningmode',
				'label'            => "XML-RPC",
				'disabled'         => false,
				'default'          => false,
				'help'     => [
					'label' => 'default',
					'url'   => 'definition/what-is-xml-rpc',
					'title' => __( "About XML-RPC", 'really-simple-ssl' ),
					'text'  => __( 'XML-RPC is a mechanism originally implemented into WordPress to publish content without the need to actually login to the backend. It is also used to login to WordPress from devices other than desktop, or the regular wp-admin interface.', 'really-simple-ssl' ),
				],
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
                        'grow'     => 2,
                        'width'    => '30%',
					],
					[
						'name'     => __( 'Login status', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'login_statusControl',
						'width'     => '20%',
					],
					[
						'name'     => __( 'Count', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'count',
						'width'     => '20%',
					],
					[
						'name'     => __( '', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'statusControl',
						'width'     => '10%',
					],
					[
						'name'     => __( '', 'really-simple-ssl' ),
						'sortable' => false,
						'column'   => 'deleteControl',
						'width'     => '10%',
					],
				],
			],
		]
	);
}, 200 );
