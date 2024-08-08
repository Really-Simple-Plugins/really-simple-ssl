<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id'       => 'enable_limited_login_attempts',
				'menu_id'  => 'limit_login_attempts',
				'group_id' => 'limit_login_attempts_general',
				'type'     => 'checkbox',
				'label'    => __('Enable Limit Login Attempts', 'really-simple-ssl'),
				'help'     => [
					'label' => 'default',
					'url'   => 'instructions/limit-login-attempts',
					'title' => __( "About Limit Login Attempts", 'really-simple-ssl' ),
					'text'  => __( 'Limit Login Attempts protects your site from login attempts by unauthorized users. When you enable Limit Login Attempts, all login attempts are logged and repeated attempts to login with invalid credentials will be blocked automatically.', 'really-simple-ssl' ),
				],
				'disabled' => false,
			],
			[
				'id'               => 'limit_login_attempts_amount',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_advanced',
				'type'             => 'select',
				'tooltip'          => __("After this number of failed login attempts the user and IP address will be temporarily blocked.",
					'really-simple-ssl'),
				'label'            => __('Login attempts', 'really-simple-ssl'),
				'options'          => [
					'3'  => sprintf('%d %s', 3, __('attempts', 'really-simple-ssl')),
					'5'  => sprintf('%d %s', 5, __('attempts', 'really-simple-ssl')),
					'10' => sprintf('%d %s', 10, __('attempts', 'really-simple-ssl')),
					'15' => sprintf('%d %s', 15, __('attempts', 'really-simple-ssl')),
				],
				'disabled'         => false,
				'default'          => '5',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_limited_login_attempts' => true,
					]
				],
			],
			[
				'id'               => 'limit_login_attempts_duration',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_advanced',
				'type'             => 'select',
				'tooltip'          => __("If the number of failed login attempts is exceeded within this timeframe, the IP address and user will be blocked.",
					'really-simple-ssl'),
				'label'            => __('Interval', 'really-simple-ssl'),
				'options'          => [
					'15'   => sprintf(__('%s minutes', 'really-simple-ssl'), 15),
					'30'   => sprintf(__('%s minutes', 'really-simple-ssl'), 30),
					'60'   => sprintf(__('%s hour', 'really-simple-ssl'), 1),
					'240'  => sprintf(__('%s hours', 'really-simple-ssl'), 4),
					'1440' => sprintf(__('%s day', 'really-simple-ssl'), 1),
				],
				'disabled'         => false,
				'default'          => '15',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_limited_login_attempts' => true,
					]
				],
			],
			[
				'id'               => 'limit_login_attempts_locked_out_duration',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_advanced',
				'type'             => 'select',
				'tooltip'          => __("The user and IP address will be temporarily unable to login for the specified duration. You can block IP addresses indefinitely via the IP addresses block.",
					'really-simple-ssl'),
				'label'            => __('Lockout duration', 'really-simple-ssl'),
				'options'          => [
					'15'   => sprintf(__('%s minutes', 'really-simple-ssl'), 15),
					'30'   => sprintf(__('%s minutes', 'really-simple-ssl'), 30),
					'60'   => sprintf(__('%s hour', 'really-simple-ssl'), 1),
					'240'  => sprintf(__('%s hours', 'really-simple-ssl'), 4),
					'1440' => sprintf(__('%s day', 'really-simple-ssl'), 1),
					'10080' => sprintf(__('%s week', 'really-simple-ssl'), 1),
					'43200' => sprintf(__('%s month', 'really-simple-ssl'), 1),
					'86400' => sprintf(__('%s months', 'really-simple-ssl'), 2),
				],
				'disabled'         => false,
				'default'          => '30',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_limited_login_attempts' => true,
					]
				],
			],
			[
				//Captchas
				'id'               => 'limit_login_attempts_captcha',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_advanced',
				'type'             => 'checkbox',
				'label'            => __('Trigger captcha on failed login attempt', 'really-simple-ssl'),
				'disabled'         => false,
				'default'          => false,
				'comment'                 => sprintf(__("Please configure your %sCaptcha settings%s before enabling this setting",
					"really-simple-ssl"), '<a id="set_to_captcha_configuration" href="#settings/general/enable_captcha_provider">', '</a>'),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_limited_login_attempts' => true,
						'captcha_fully_enabled' => true,
					],
				],
			],
			[
				'id'               => 'limit_login_attempts_users_view',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_users',
				'type'             => 'user-datatable',
				'action'           => 'rsssl_limit_login_user',
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
						'enable_limited_login_attempts' => true,
					]
				],
				'columns'          => [
					[
						'name'       => __('Username', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'attempt_value',
						'width'      => '50%',
					],
					[
						'name'     => __('Status', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'status',
						'width'    => '10%',
					],
					[
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'datetime',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
					],
				],
			],
			[
				'id'               => 'limit_login_attempts_ip_view',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_ip_address',
				'type'             => 'ip-address-datatable',
				'action'           => 'rsssl_limit_login',
				'options'          => [
					'blocked' => __('Blocked', 'really-simple-ssl'),
					'locked'  => __('Locked-out', 'really-simple-ssl'),
					'trusted' => __('Trusted', 'really-simple-ssl'),
				],
				'label'            => __('Enable open source blocklist API etc.', 'really-simple-ssl'),
				'disabled'         => false,
				'default'          => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'enable_limited_login_attempts' => true,
					]
				],
				'columns'          => [
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'attempt_value',
						'width'      => '50%',
					],
					[
						'name'     => __('Status', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'status',
						'width'    => '10%',
					],
					[
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'datetime',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
					],
				],
			],
			[
				'id'               => 'limit_login_attempts_country_view',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_country',
				'type'             => 'country-datatable',
				'action'           => 'rsssl_limit_login_country',
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
						'enable_limited_login_attempts' => true,
					]
				],
				'columns'          => [
					[
						'name'       => '',
						'sortable'   => false,
						'searchable' => true,
						'column'     => 'attempt_value',
						'width'      => '4%',
					],
					[
						'name'       => __('Country', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'visible'   => false,
						'column'     => 'country_name',
						'width'      => '200px',
					],
					[
						'name'       => __('Continent', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'region_name',
						'width'      => '20%',
					],
					[
						'name'     => __('Status', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'status',
						'width'    => '10%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
					],
				],
			],
			[
				'id' 			 => 'event_log_enabled',
				'menu_id'        => 'limit_login_attempts',
				'group_id'       => 'limit_login_attempts_event_log',
				'type'           => 'hidden',
				'default'        => false,
			],
			[
				'id'               => 'event_log_viewer',
				'menu_id'          => 'limit_login_attempts',
				'group_id'         => 'limit_login_attempts_event_log',
				'type'             => 'eventlog-datatable',
				'event_type'        => 'login-protection',
				'action'           => 'event_log',
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
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'datetime',
						'width'         => '12%',
					],
					[
						'name'       => __('User', 'really-simple-ssl'),
						'sortable'   => true,
						'column'     => 'username',
						'searchable' => true,
						'type'       => 'text',
						'width'     => '12%',
					],
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'source_ip',
						'type'     => 'text',
						'width'    => '32%',
					],
					[
						'name'     => __('Event', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'event_name',
						'width'         => '28%',
					]
				],
			],
		]
	);
}, 200 );
