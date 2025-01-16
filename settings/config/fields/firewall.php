<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id' => 'enable_firewall',
				'menu_id' => 'rules',
				'group_id' => 'firewall_list_general',
				'type' => 'checkbox',
				'label' => __( 'Enable Firewall', 'really-simple-ssl' ),
				'default' => false,
			],
			[
				'id'      => 'firewall_white_listing_overview',
				'menu_id' => 'firewall_blocklists',
				'group_id' => 'firewall_white_list_listing',
				'type'    => 'geo-ip-datatable',
				'action'  => 'rsssl_geo_white_list',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
					]
				],
				'columns' => [
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'ip_address',
						'width'      => '45%',
					],
					[
						'name'     => __('Note', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'note',
						'width'    => '20%',
					],
					[
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'create_date',
						'width'    => '18%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
						'width'    => '13%',
					],
				],
			],
			[
				'id'      => 'firewall_block_listing_overview',
				'menu_id' => 'firewall_blocklists',
				'group_id' => 'firewall_block_list_listing',
				'type'    => 'blocklist-datatable',
				'action'  => 'rsssl_firewall_block_list',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
					]
				],
				'columns' => [
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'ip_address',
						'width'      => '55%',
					],
					[
						'name'     => __('Note', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'note',
						'width'    => '22%',
					],
					[
						'name'     => __('Time left', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'time_left',
						'width'    => '10%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
						'width'    => '13%',
					],
				],
			],
			[
				'id'    => '404_blocking_threshold',
				'menu_id' => 'rules',
				'group_id' => '404_blocking',
				'type' => 'select',
				'label' => __( 'Threshold', 'really-simple-ssl' ),
				'tooltip' => sprintf(__('A lockout will occur if an IP address exceeds the threshold within the given timeframe. Select ‘%s’ if you want to disable 404 blocking.', 'really-simple-ssl'), __('Disabled', 'really-simple-ssl')),
				'default' => 'lax',
				'disabled' => rsssl_maybe_disable_404_blocking(),
				'options' => [
					'disabled' => __( 'Disabled', 'really-simple-ssl' ),
					'lax' => __( 'Lax - 10 errors in 2 seconds', 'really-simple-ssl' ),
					'normal' => __( 'Normal - 10 errors in 5 seconds', 'really-simple-ssl' ),
					'strict' => __( 'Strict - 10 errors in 10 seconds', 'really-simple-ssl' ),
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
					]
				],
			],
			[
				'id'    => '404_blocking_lockout_duration',
				'menu_id' => 'rules',
				'group_id' => '404_blocking',
				'type' => 'select',
				'label' => __( 'Lockout duration', 'really-simple-ssl' ),
				'tooltip' => __('The IP address will see a locked out screen for the selected duration.', 'really-simple-ssl'),
				'disabled' => rsssl_maybe_disable_404_blocking(),
				'options' => [
					'30' => __( '30 minutes', 'really-simple-ssl' ),
					'60' => __( '1 hour', 'really-simple-ssl' ),
					'240' => __( '4 hours', 'really-simple-ssl' ),
					'1440' => __( '1 day', 'really-simple-ssl' ),
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
					]
				],
			],
			[
				'id'    => '404_blocking_captcha_trigger',
				'menu_id' => 'rules',
				'group_id' => '404_blocking',
				'type' => 'checkbox',
				'tooltip' => __('Allow visitors that might accidentally exceed the threshold to unblock themselves using a Captcha.', 'really-simple-ssl'),
				'label' => __( 'Trigger Captcha on lockout', 'really-simple-ssl' ),
				'disabled'         => false,
				'default'          => false,
				'comment'                 => sprintf(__( 'Please configure your %sCaptcha settings%s before enabling this setting',
					'really-simple-ssl' ), '<a id="set_to_captcha_configuration" href="#settings/general/enable_captcha_provider">', '</a>'),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
						'captcha_fully_enabled' => true,
					],
				],
			],
            [
                'id'               => 'user_agent_listing_overview',
                'menu_id'          => 'rules',
                'group_id'         => 'user_agents',
                'type'             => 'user-agents-datatable',
                'action'           => 'rsssl_user_agent_list',
                'options'          => [
                    'deleted' => __('Deleted', 'really-simple-ssl'),
                    'blocked'  => __('Blocked', 'really-simple-ssl'),
                ],
                'disabled'         => false,
                'default'          => false,
                'react_conditions' => [
                    'relation' => 'AND',
                    [
                        'enable_firewall' => true,
                    ]
                ],
                'columns'          => [
                    [
                        'name'       => __('User-Agent', 'really-simple-ssl'),
                        'sortable'   => true,
                        'searchable' => true,
                        'column'     => 'user_agent',
                        'width'      => '20%',
                    ],
                    [
                        'name'    => __('Note', 'really-simple-ssl'),
                        'sortable' => false,
                        'searchable' => false,
                        'column'  => 'note',
                        'width'   => '40%',
                    ],
                    [
                        'name'     => __('Date Added', 'really-simple-ssl'),
                        'sortable' => false,
                        'column'   => 'created_at',
                        'width'    => '20%',
                    ],
                    [
                        'name'     => '',
                        'sortable' => false,
                        'column'   => 'action',
                    ],
                ],
            ],
			[
				'id'               => 'firewall_listing_overview',
				'menu_id'          => 'rules',
				'group_id'         => 'firewall_list_listing',
				'type'             => 'geo-datatable',
				'action'           => 'rsssl_geo_list',
				'options'          => [
					'blocked' => __('Blocked', 'really-simple-ssl'),
					'locked'  => __('Locked-out', 'really-simple-ssl'),
					'trusted' => __('Trusted', 'really-simple-ssl'),
				],
				'disabled'         => false,
				'default'          => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_firewall' => true,
					]
				],
				'columns'          => [
					[
						'name'       => __('', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => false,
						'column'     => 'flag',
						'width'      => '5%',
					],
					[
						'name'       => __('Country', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'country_name',
						'width'      => '150px',
					],
					[
						'name'    => __('Continent', 'really-simple-ssl'),
						'sortable' => false,
						'searchable' => false,
						'column'  => 'region_name',
						'width'   => '30%',
					],
					[
						'name'     => __('Status', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'status',
						'width'    => '20%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
						'width'    => '180px',
					],
				],
			],
			[
				'id'               => 'firewall_event_log_viewer',
				'menu_id'          => 'firewall_logs',
				'group_id'         => 'firewall_logs_content',
				'type'             => 'eventlog-datatable',
				'action'           => 'event_log',
				'event_type'       => 'Firewall',
				'label'            => __('IP address overview', 'really-simple-ssl'),
				'disabled'         => false,
				'default'          => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'event_log_enabled' => true,
					]
				],
				'columns'          => [
					[
						'name'     => __('Country', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'iso2_code',
						'width'    => '8%',
					],
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'source_ip',
						'type'     => 'text',
						'width'    => '42%',
					],
					[
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'datetime',
						'width'         => '20%',
					],
					[
						'name'     => __('Event', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'event_name',
						'width'         => '25%',
					]
				],
			],
		]
	);
}, 200 );
