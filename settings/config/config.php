<?php
defined( 'ABSPATH' ) or die();
function rsssl_fields( $load_values = true ) {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$fields = [
		[
			'id'       => 'ssl_enabled',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => 'ssl_enabled',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'site_has_ssl',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'other_host_type',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'host',
			//options loaded in data store
			'default'  => false,
			'label'    => __( "Hosting provider", 'really-simple-ssl' ),
			'required' => false,
			'disabled' => false,
		],
		[
			'id'       => 'review_notice_shown',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'dismiss_all_notices',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __("Dismiss all notifications", 'really-simple-ssl'),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'          => 'download-system-status',
			'menu_id'     => 'general',
			'group_id'    => 'general',
			'type'        => 'button',
			'url'         => trailingslashit(rsssl_url).'system-status.php?download',
			'button_text' => __("Download", "really-simple-ssl"),
			'label'       => __("System status", 'really-simple-ssl'),
			'disabled'    => false,
			'default'     => false,
		],
		[
			'id'       => 'delete_data_on_uninstall',
			'menu_id'  => 'general',
			'group_id' => 'general',
			'type'     => 'checkbox',
			'label'    => __("Delete all data on plugin deletion", 'really-simple-ssl'),
			'default'  => false,
		],
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
				'url'       => 'https://really-simple-ssl.com/remove-htaccess-redirect-site-lockout/',
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
			'help'             => [
				'label' => 'default',
				'title' => __("Mixed content fixer - init hook", 'really-simple-ssl'),
				'text'  => __('If this option is set to true, the mixed content fixer will fire on the init hook instead of the template_redirect hook. Only use this option when you experience problems with the mixed content fixer.',
					'really-simple-ssl'),
			],
			'disabled'         => false,
			'required'         => false,
			'default'          => false,
			'tooltip'          => __("Only enable if the default mixed content fixer does not fix your front-end mixed content.",
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
			'id'       => 'notifications_email_address',
			'menu_id'  => 'general',
			'group_id' => 'general_email',
			'type'     => 'email',
			'label'    => __( "Email address", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => get_bloginfo('admin_email'),
		],
		[
			'id'       => 'send_verification_email',
			'menu_id'  => 'general',
			'group_id' => 'general_email',
			'type'     => 'button',
			'action'      => 'send_verification_mail',
			'button_text' => __( "Send", "really-simple-ssl" ),
			'warning'     => true,
			'label'       => __( "Email verification", 'really-simple-ssl' ),
			'disabled'    => rsssl_is_email_verified(),
			'tooltip'  => __( "Verify your email address to get the most out of Really Simple SSL.", 'really-simple-ssl' ),
		],
		[
			'id'               => 'send_notifications_email',
			'menu_id'          => 'general',
			'group_id'         => 'general_email',
			'type'             => 'checkbox',
			'label'      => __("Notifications by email", 'really-simple-ssl'),
			'tooltip'            => __("Get notified of important changes, updates and settings. Recommended when using security features.", 'really-simple-ssl'),
			'disabled'         => false,
			'default'          => false,
		],
		[
			'id'       => 'premium_support',
			'menu_id'  => 'general',
			'group_id' => 'support',
			'type'     => 'support',
			'label'    => __("Premium support", 'really-simple-ssl'),
			'disabled' => false,
			'default'  => false,
		],
		/* section limited_login_attempts */
		[
			'id'       => 'enable_limited_login_attempts',
			'menu_id'  => 'limit_login_attempts',
			'group_id' => 'limit_login_attempts_general',
			'type'     => 'checkbox',
			'label'    => __('Enable Limit Login Attempts', 'really-simple-ssl'),
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/instructions/limit-login-attempts/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About Limit Login Attempts", 'really-simple-ssl' ),
				'text'  => __( 'Limit Login Attempts protects your site from login attempts by unauthorized users. When you enable Limit Login Attempts, all login attempts are logged and repeated attempts to login with invalid credentials will be blocked automatically.', 'really-simple-ssl' ).' '.__('They might be misused if you don’t actively tell the browser to disable these features.', 'really-simple-ssl' ),
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
			'id'               => 'limit_login_attempts_users_view',
			'menu_id'          => 'limit_login_attempts',
			'group_id'         => 'limit_login_attempts_users',
			'type'             => 'user-datatable',
			'action'           => 'user_list',
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
			'action'           => 'ip_list',
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
			'action'           => 'country_list',
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
					'width'      => '20%',
				],
				[
					'name'       => __('Continent', 'really-simple-ssl'),
					'sortable'   => true,
					'searchable' => true,
					'column'     => 'region',
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
			'id'               => 'event_log_viewer',
			'menu_id'          => 'limit_login_attempts',
			'group_id'         => 'limit_login_attempts_event_log',
			'type'             => 'eventlog-datatable',
			'action'           => 'event_log',
			'label'            => __('IP address overview', 'really-simple-ssl'),
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
//
//                [
//                    'width'    => '1px',
//                ]

			],
		],
		[
			'id'       => 'x_xss_protection',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'label'    => __("X-XSS-Protection", "really-simple-ssl"),
			'options'  => [
				'disabled'   => __("disabled", "really-simple-ssl"),
				'zero'       => "0 ".__("(recommended)", "really-simple-ssl"),
				'one'        => "1",
				'mode_block' => "1; mode=block",
			],
			'disabled' => false,
			'default'  => 'zero',
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/about-recommended-security-headers/?mtm_campaign=definition&mtm_source=free',
				'title' => __("About Recommended Security Headers", 'really-simple-ssl'),
				'text'  => __('These security headers are the fundamental security measures to protect your website visitors while visiting your website.',
					'really-simple-ssl'),
			],
		],
		[
			'id'       => 'x_content_type_options',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'checkbox',
			'label'    => __("X-Content-Type options", "really-simple-ssl"),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'x_frame_options',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'options'  => [
				'disabled'   => __("Off", "really-simple-ssl"),
				'DENY'       => 'DENY',
				'SAMEORIGIN' => 'SAMEORIGIN',
			],
			'label'    => __("X-Frame options", "really-simple-ssl"),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'referrer_policy',
			'menu_id'  => 'recommended_security_headers',
			'group_id' => 'recommended_security_headers',
			'type'     => 'select',
			'options'  => [
				'disabled'                        => __("Off", "really-simple-ssl"),
				'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin'.' ('.__("recommended",
						"really-simple-ssl").')',
				'no-referrer'                     => 'no-referrer',
				'origin'                          => 'origin',
				'no-referrer-when-downgrade'      => 'no-referrer-when-downgrade',
				'unsafe-url'                      => 'unsafe-url',
				'origin-when-cross-origin'        => 'origin-when-cross-origin',
				'strict-origin'                   => 'strict-origin',
				'same-origin'                     => 'same-origin',
			],
			'label'    => __("Referrer Policy", "really-simple-ssl"),
			'disabled' => false,
			'default'  => 'strict-origin-when-cross-origin',
		],
		[
			'id'               => 'hsts',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'checkbox',
			'label'            => __("HTTP Strict Transport Security", "really-simple-ssl"),
			'disabled'         => false,
			'default'          => false,
			'help'             => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-hsts/?mtm_campaign=definition&mtm_source=free',
				'title' => __("About HTTP Strict Transport Security", 'really-simple-ssl'),
				'text'  => __('Leveraging your SSL certificate with HSTS is a staple for every website. Force your website over SSL, mitigating risks of malicious counterfeit websites in your name.',
					'really-simple-ssl'),
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'ssl_enabled' => '1',
				]
			],
		],
		[
			'id'                      => 'hsts_preload',
			'menu_id'                 => 'hsts',
			'group_id'                => 'hsts',
			'type'                    => 'checkbox',
			'label'                   => __("Include preload", "really-simple-ssl"),
			'comment'                 => sprintf(__("After enabling this feature, you can submit your site to %shstspreload.org%s",
				"really-simple-ssl"), '<a target="_blank" href="https://hstspreload.org?domain='.site_url().'">',
				"</a>"),
			'react_conditions'        => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'configure_on_activation' => [
				'condition' => 1,
				[
					'hsts_subdomains' => true,
					'hsts_max_age'    => 63072000,
				]
			],
			'disabled'                => false,
			'default'                 => false,
		],
		[
			'id'               => 'hsts_subdomains',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'checkbox',
			'label'            => __("Include subdomains", "really-simple-ssl"),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'disabled'         => false,
			'default'          => false,
		],
		[
			'id'               => 'hsts_max_age',
			'menu_id'          => 'hsts',
			'group_id'         => 'hsts',
			'type'             => 'select',
			'options'          => [
				'86400'    => __('One day (for testing only)', 'really-simple-ssl'),
				'31536000' => __('One year', 'really-simple-ssl'),
				'63072000' => __('Two years (required for preload)', 'really-simple-ssl'),
			],
			'label'            => __("Choose the max-age for HSTS", "really-simple-ssl"),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'hsts' => true,
				]
			],
			'disabled'         => false,
			'default'          => '63072000',
		],
		[
			'id'       => 'cross_origin_opener_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'                 => __('Off', 'really-simple-ssl'),
				'unsafe-none'              => 'unsafe-none',
				'same-origin-allow-popups' => 'same-origin-allow-popups',
				'same-origin'              => 'same-origin',
			],
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-cross-origin-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __("About Cross Origin Policies", 'really-simple-ssl'),
				'text'  => __('One of the most powerful features, and therefore the most complex are the Cross-Origin headers that can isolate your website so any data leaks are minimized.',
					'really-simple-ssl'),
			],
			'label'    => __("Cross Origin Opener Policy", "really-simple-ssl"),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'cross_origin_resource_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'     => __('Off', 'really-simple-ssl'),
				'same-site'    => 'same-site',
				'same-origin'  => 'same-origin',
				'cross-origin' => 'cross-origin',
			],
			'label'    => __("Cross Origin Resource Policy", "really-simple-ssl"),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'cross_origin_embedder_policy',
			'menu_id'  => 'cross_origin_policy',
			'group_id' => 'cross_origin_policy',
			'type'     => 'select',
			'options'  => [
				'disabled'     => __('Off', 'really-simple-ssl'),
				'require-corp' => 'require-corp',
				'same-origin'  => 'same-origin',
				'unsafe-none'  => 'unsafe-none',
			],
			'label'    => __("Cross Origin Embedder Policy", "really-simple-ssl"),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'                   => 'do_not_edit_htaccess',
			'menu_id'              => 'general',
			'group_id'             => 'general',
			'type'                 => 'checkbox',
			'label'                => __( "Stop editing the .htaccess file", 'really-simple-ssl' ),
			'disabled'             => false,
			'default'              => false,
			//on multisite this setting can only be set networkwide
			'networkwide_required' => true,
			'server_conditions'    => [
				'relation' => 'AND',
				[
					'RSSSL()->server->uses_htaccess()' => true,
				]
			],
		],
		[
			'id'                 => 'disable_anyone_can_register',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable \"anyone can register\"", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'help'               => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-are-hardening-features/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About Hardening", 'really-simple-ssl' ),
				'text'  => __( 'Hardening features limit the possibility of potential weaknesses and vulnerabilities which can be misused.', 'really-simple-ssl' ),
			],
			'recommended'        => true,
		],
		[
			'id'                 => 'disable_file_editing',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable the built-in file editors", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'recommended'        => true,
		],
		[
			'id'                 => 'block_code_execution_uploads',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Prevent code execution in the public 'Uploads' folder", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'recommended' => true,
		],
		[
			'id'       => 'hide_wordpress_version',
			'menu_id'  => 'hardening-basic',
			'group_id' => 'hardening-basic',
			'type'     => 'checkbox',
			'label'    => __( "Hide your WordPress version", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
			'recommended' => true,
		],
		[
			'id'       => 'disable_login_feedback',
			'menu_id'  => 'hardening-basic',
			'group_id' => 'hardening-basic',
			'type'     => 'checkbox',
			'tooltip'  => __( "By default, WordPress shows if a username or email address exists when a login fails. This will change it to generic feedback.", 'really-simple-ssl' ),
			'label'    => __( "Prevent login feedback", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
			'recommended' => true,
		],
		[
			'id'                 => 'disable_indexing',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable directory browsing", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'recommended' => true,
		],
		[
			'id'                 => 'disable_user_enumeration',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Disable user enumeration", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
			'recommended' => true,
		],
		[
			'id'                 => 'rename_admin_user',
			'menu_id'            => 'hardening-basic',
			'warning'     			=> true,
			'group_id'           => 'hardening-basic',
			'type'               => 'checkbox',
			'label'              => __( "Block the username 'admin'", 'really-simple-ssl' ),
			'email'              => [
				'title'   => __( "Settings update: Username 'admin' renamed", 'really-simple-ssl' ),
				'message' => sprintf(__( "As a security precaution, the username ‘admin’ has been changed on %s. From now on, you can login with '%s' or an email address.", 'really-simple-ssl' ), '{site_url}','{username}'),
				'url'     => 'https://really-simple-ssl.com/instructions/locked-our-after-renaming-the-admin-username/',
				'condition'    => 'rsssl_username_admin_changed',
			],
			'tooltip'            => __( "If the username 'admin' currently exists, you can rename it here. Please note that you can no longer use this username, and should use the new username or an email address",
				'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
		],
		[
			'id'                 => 'new_admin_user_login',
			'menu_id'            => 'hardening-basic',
			'group_id'           => 'hardening-basic',
			'type'               => 'text',
			'label'              => __( "Choose new username to replace 'admin'", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => '',
			'required'           => true,
			'condition_action'   => 'hide',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'rename_admin_user' => 1,
				]
			],
			'server_conditions' => [
				'relation' => 'AND',
				[
					'rsssl_has_admin_user()' => true,
				]
			],

		],
		[
			'id'       => 'disable_xmlrpc',
			'menu_id'  => 'hardening-basic',
			'group_id' => 'hardening-basic',
			'type'     => 'checkbox',
			'label'    => __( "Disable XML-RPC", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'block_display_is_login',
			'menu_id'  => 'hardening-basic',
			'group_id' => 'hardening-basic',
			'type'     => 'checkbox',
			'label'    => __( "Block user registrations when login and display name are the same", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'disable_http_methods',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'type'     => 'checkbox',
			'tooltip'  => __( "This will limit or fully disable HTTP requests that are not needed, but could be used with malicious intent.", 'really-simple-ssl' ),
			'label'    => __( "Disable HTTP methods", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'rename_db_prefix',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'email'            => [
				'title'   => __( "Settings update: Database prefix changed", 'really-simple-ssl' ),
				'message' => __( "Security through obscurity. Your site is no longer using the default wp_ prefix for database tables. The process has been designed to only complete and replace the tables after all wp_ tables are successfully renamed. In the unlikely event that this does lead to database issues on your site, please navigate to our troubleshooting article.", 'really-simple-ssl' ),
				'url'     => 'https://really-simple-ssl.com/instructions/database-issues-after-changing-prefix/',
			],
			'tooltip'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
			'warning'  => __( "This will permanently change your database prefixes and you can NOT rollback this feature. Please make sure you have a back-up.", 'really-simple-ssl' ),
			'type'     => 'checkbox',
			'label'    => __( "Rename and randomize your database prefix", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'                 => 'change_debug_log_location',
			'group_id'           => 'hardening-extended',
			'menu_id'            => 'hardening-extended',
			'type'               => 'checkbox',
			'tooltip'  => __( "A debug.log is publicly accessibile and has a standard location. This will change the location to a randomly named folder in /wp-content/", 'really-simple-ssl' ),
			'email'            => [
				'title'   => __( "Settings update: Debug.log file relocated", 'really-simple-ssl' ),
				'message' => __( "From now on, the debug.log won’t be publicly accessible whenever wp-debugging is enabled. The debug log will be stored in a randomly named folder in /wp-content/. This prevents possible leakage of sensitive debugging information.", 'really-simple-ssl' ),
				'url'     => 'https://really-simple-ssl.com/instructions/debug-log-has-been-relocated-but-where/',
			],
			'label'              => __( "Change debug.log file location", 'really-simple-ssl' ),
			'disabled'           => false,
			'default'            => false,
		],
		[
			'id'       => 'disable_application_passwords',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'type'     => 'checkbox',
			'label'    => __( "Disable application passwords", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'block_admin_creation',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'type'     => 'checkbox',
			'warning'  => __( "This setting will block attempts to assign administrator roles outside the native user creation process by WordPress. This might include other plugins that create, edit or assign roles to users. If you need to create an administrator in a third-party plugin, temporarily disable this setting while you make the changes.", 'really-simple-ssl' ),
			'tooltip'  => __( "This setting will block attempts to assign administrator roles outside the native user creation process by WordPress. This might include other plugins that create, edit or assign roles to users. If you need to create an administrator in a third-party plugin, temporarily disable this setting while you make the changes.", 'really-simple-ssl' ),
			'help'               => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/instructions/about-hardening-features/',
				'title' => __( "Unauthorized administrators", 'really-simple-ssl' ),
				'text'  => __( 'Many vulnerabilities are exploited by injecting a user with administrator capabilities outside of the native WordPress creation process. Under advanced hardening you can prevent this from happening.', 'really-simple-ssl' ),
			],
			'label'    => __( "Restrict creation of administrator roles", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'change_login_url_enabled',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'warning'  => true,
			'type'     => 'checkbox',
			'tooltip'  => __( "Allows you to enter a custom login URL.", 'really-simple-ssl' ),
			'label'    => __( "Enable Custom login URL", 'really-simple-ssl' ),
			'email'            => [
				'title'   => __( "You have changed your login URL", 'really-simple-ssl' ),
				'message' => __( "Your login URL has changed to {login_url} to prevent common bot attacks on standard login URLs. Learn more about this feature, common questions and measures to prevent any issues.", 'really-simple-ssl' ),
				'url'     => 'https://really-simple-ssl.com/instructions/login-url-changed',
			],
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'change_login_url',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'type'     => 'text',
			'tooltip'  => __( "Enter a custom login URL. This allows you to log in via this custom URL instead of /wp-admin or /wp-login.php", 'really-simple-ssl' ),
			'placeholder'  => __( "Example: If you want to change your login page from /wp-admin/ to /control/ answer: control", 'really-simple-ssl' ),
			'label'    => __( "Custom login URL", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
			'condition_action'   => 'hide',
			'pattern' => '[a-zA-Z0-9\-_]+',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'change_login_url_enabled' => 1,
				]
			],
		],
		[
			'id'       => 'change_login_url_failure_url',
			'menu_id'  => 'hardening-extended',
			'group_id' => 'hardening-extended',
			'type'     => 'postdropdown',
			'tooltip'  => __( "Users trying to enter via /wp-admin or /wp-login.php will be redirected to this URL.", 'really-simple-ssl' ),
			'label'    => '',
			'disabled' => false,
			'default'  => '404_default',
			'condition_action'   => 'hide',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'change_login_url_enabled' => 1,
				]
			],
		],
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
			'menu_id'          => 'hardening-xml',
			'group_id'         => 'hardening-xml',
			'type'             => 'learningmode',
			'label'            => __( "XML-RPC", 'really-simple-ssl' ),
			'disabled'         => false,
			'default'          => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-xml-rpc/',
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
					'width'     => '20%',
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
					'name'     => '',
					'sortable' => false,
					'column'   => 'statusControl',
					'width'     => '20%',
				],
				[
					'name'     => '',
					'sortable' => false,
					'column'   => 'deleteControl',
					'width'     => '20%',
				],
			],
		],
		[
			'id'       => 'login_protection_enabled',
			'menu_id'  => 'two-fa',
			'group_id' => 'two_fa_general',
			'type'     => 'checkbox',
			'label'    => __( "Enable login protection", "really-simple-ssl" ),
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/instructions/about-login-protection/?mtm_campaign=instructions&mtm_source=2fa',
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
		[
			'id'          => 'mixedcontentscan',
			'menu_id'     => 'encryption',
			'group_id'    => 'mixed-content-scan',
			'type'        => 'mixedcontentscan',
			'label'       => __( "Mixed content scan", "really-simple-ssl" ),
			'help'        => [
				'label' => 'default',
				'url' => 'https://really-simple-ssl.com/definition/what-is-mixed-content/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Mixed Content Scan", 'really-simple-ssl' ),
				'text'  => __( 'The extensive mixed content scan will list all current and future issues and provide a fix, or instructions to fix manually.', 'really-simple-ssl' ),
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
		[
			'id'       => 'permissions_policy',
			'menu_id'  => 'permissions_policy',
			'group_id' => 'permissions_policy',
			'type'     => 'permissionspolicy',
			'options'  => [ '*' => __( "Allow", "really-simple-ssl" ), '()' => __( "Disable", "really-simple-ssl" ), 'self' => __( "Self (Default)", "really-simple-ssl" ) ],
			'label'    => __( "Permissions Policy", 'really-simple-ssl' ),
			'disabled' => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-permissions-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Permission Policy", 'really-simple-ssl' ),
				'text'  => __( 'Browser features are plentiful, but most are not needed on your website.', 'really-simple-ssl' ).' '.__('They might be misused if you don’t actively tell the browser to disable these features.', 'really-simple-ssl' ),
			],
			'columns'  => [
				[
					'name'     => __( 'Feature', 'really-simple-ssl' ),
					'sortable' => true,
					'column'   => 'title',
				],
				[
					'name'     => __( '', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'valueControl',
				],
			],
			'default'  => [
				[
					'id'     => 'accelerometer',
					'title'  => 'Accelerometer',
					'value'  => 'self',
					'status' => true,
				],
				[
					'id'     => 'autoplay',
					'title'  => 'Autoplay',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'camera',
					'title'  => 'Camera',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'encrypted-media',
					'title'  => 'Encrypted Media',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'fullscreen',
					'title'  => 'Fullscreen',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'geolocation',
					'title'  => 'Geolocation',
					'value'  => '*',
					'status' => false,
				],
				[
					'id'     => 'microphone',
					'title'  => 'Microphone',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'midi',
					'title'  => 'Midi',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'payment',
					'title'  => 'Payment',
					'value'  => 'self',
					'status' => false,
				],
				[
					'id'     => 'display-capture',
					'title'  => 'Display Capture',
					'value'  => 'self',
					'status' => false,
				],
			],
		],
		[
			'id'       => 'enable_permissions_policy',
			'menu_id'  => 'permissions_policy',
			'group_id' => 'permissions_policy',
			'type'     => 'hidden',
			'label'    => __( "Enable Permissions Policy", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'upgrade_insecure_requests',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'upgrade_insecure_requests',
			'type'     => 'checkbox',
			'label'    => __( "Serve encrypted and authenticated responses", 'really-simple-ssl' ),
			'disabled' => false,
			'default'  => false,
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/definition/what-is-a-content-security-policy/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "About the Content Security Policy", 'really-simple-ssl' ),
				'text'  => __( 'The content security policy has many options, so we always recommend starting in ‘learning mode’ to see what files and scripts are loaded.', 'really-simple-ssl' ),
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'ssl_enabled' => '1',
				]
			],
		],
		[
			'id'       => 'csp_frame_ancestors',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'frame_ancestors',
			'type'     => 'select',
			'options'  => [
				'disabled' => __( "Disable (Default)", "really-simple-ssl" ),
				'none'     => "None",
				'self'     => "Self",
			],
			'label'    => __( "Allow your domain to be embedded", "really-simple-ssl" ),
			'disabled' => false,
			'default'  => 'disabled',
		],
		[
			'id'       => 'csp_frame_ancestors_urls',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'frame_ancestors',
			'type'     => 'textarea',
			'label'    => __( "Add additional domains which can embed your website, if needed. Comma seperated.", "really-simple-ssl" ),
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'csp_status',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'content_security_policy_source_directives',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'       => 'csp_status_lm_enabled_once',
			'menu_id'  => 'content_security_policy',
			'group_id' => 'content_security_policy_source_directives',
			'type'     => 'hidden',
			'label'    => '',
			'disabled' => false,
			'default'  => false,
		],
		[
			'id'            => 'content_security_policy_source_directives',
			'control_field' => 'csp_status',
			'menu_id'       => 'content_security_policy',
			'group_id'      => 'content_security_policy_source_directives',
			'type'          => 'learningmode',
			'label'         => "Content Security Policy",
			'disabled'      => false,
			'default'       => false,
			'columns'       => [
				[
					'name'     => __( 'Location', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'documenturi',
					'grow'     => 2,
				],
				[
					'name'     => __( 'Directive', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'violateddirective',
					'grow'     => 1,
				],
				[
					'name'     => __( 'Source', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'blockeduri',
					'grow'     => 1,
				],
				[
					'name'     => __( 'Action', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'statusControl',
					'grow'     => 1,
				],
				[
					'name'     => __('Delete', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'deleteControl',
					'grow'     => 1,
				],
				[   //placeholder until we have resolved the columns
					'name'     => '',
				],
			],
		],
		/* Vulnerability basic Section */
		[
			'id' => 'enable_vulnerability_scanner',
			'menu_id' => 'vulnerabilities_basic',
			'group_id' => 'vulnerabilities_basic',
			'type' => 'checkbox',
			'label' => __('Vulnerability detection', 'really-simple-ssl'),
			'tooltip'  => __( "This feature depends on multiple standard background processes. If a process fails or is unavailable on your system, detection might not work. We run frequent tests for this purpose. We will notify you accordingly if there are any issues.", 'really-simple-ssl' ),
			'disabled' => false,
			'default' => false,
			'warning' => true,
			'help'               => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/instructions/about-vulnerabilities/',
				'title' => __( "About Vulnerabilities", 'really-simple-ssl' ),
				'text'  => __( 'Really Simple SSL collects information about plugins, themes, and core vulnerabilities from our database powered by WPVulnerability. Anonymized data about these vulnerable components will be sent to Really Simple SSL for statistical analysis to improve open-source contributions. For more information, please read our privacy statement.', 'really-simple-ssl' ),
			],
		],
		[
			'id' => 'vulnerabilities_intro_shown',
			'menu_id' => 'vulnerabilities_basic',
			'group_id' => 'vulnerabilities_basic',
			'type' => 'hidden',
			'label' => '',
			'disabled' => false,
			'default' => false,
		],
		[
			'id' => 'enable_feedback_in_plugin',
			'menu_id' => 'vulnerabilities_basic',
			'group_id' => 'vulnerabilities_basic',
			'tooltip'  => __( "If there's a vulnerability, you will also get feedback on the themes and plugin overview.", 'really-simple-ssl' ),
			'warning' => false,
			'type' => 'checkbox',
			'label' => __('Feedback in plugin overview', 'really-simple-ssl'),
			'disabled' => false,
			'default' => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
				]
			],
		],
		/* Vulnerability advanced Section */
		[
			'id' => 'vulnerability_notification_dashboard',
			'menu_id' => 'vulnerabilities_notifications',
			'group_id' => 'vulnerabilities_notifications',
			'type' => 'select',
			'options' => [
				'*' => __('None', 'really-simple-ssl'),
				'l' => __('Low-risk (default)', 'really-simple-ssl'),
				'm' => __('Medium-risk', 'really-simple-ssl'),
				'h' => __('High-risk', 'really-simple-ssl'),
				'c' => __('Critical', 'really-simple-ssl'),
			],
			'label' => __('Really Simple SSL dashboard', 'really-simple-ssl'),
			'disabled' => false,
			'default' => 'l',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
				]
			],
		],
		[
			'id' => 'vulnerability_notification_sitewide',
			'menu_id' => 'vulnerabilities_notifications',
			'group_id' => 'vulnerabilities_notifications',
			'type' => 'select',
			'options' => [
				'*' => __('None', 'really-simple-ssl'),
				'l' => __('Low-risk ', 'really-simple-ssl'),
				'm' => __('Medium-risk', 'really-simple-ssl'),
				'h' => __('High-risk (default)', 'really-simple-ssl'),
				'c' => __('Critical', 'really-simple-ssl'),
			],
			'label' => __('Site-wide, admin notification', 'really-simple-ssl'),
			'disabled' => false,
			'default' => 'h',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
				]
			],
		],
		[
			'id' => 'vulnerability_notification_email_admin',
			'menu_id' => 'vulnerabilities_notifications',
			'group_id' => 'vulnerabilities_notifications',
			'type' => 'select',
			'options' => [
				'*' => __('None', 'really-simple-ssl'),
				'l' => __('Low-risk', 'really-simple-ssl'),
				'm' => __('Medium-risk', 'really-simple-ssl'),
				'h' => __('High-risk', 'really-simple-ssl'),
				'c' => __('Critical (default)', 'really-simple-ssl'),
			],
			'label' => __('Email', 'really-simple-ssl'),
			'tooltip'  => __( "This will send emails about vulnerabilities directly from your server. Make sure you can receive emails by the testing a preview below. If this feature is disabled, please enable notifications under general settings.", 'really-simple-ssl' ),
			'warning' => true,
			'disabled' => false,
			'default' => 'c',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
				],
				[
					'send_notifications_email' => 1,
				]
			],
		],
		[
			'id' => 'vulnerabilities_test',
			'menu_id' => 'vulnerabilities_notifications',
			'group_id' => 'vulnerabilities_notifications',
			'type' => 'notificationtester',
			'action' => 'test_vulnerability_notification',
			'label' => __('Test notifications', 'really-simple-ssl'),
			'tooltip' => __('Test notifications can be used to test email delivery and shows how vulnerabilities will be reported on your WordPress installation.', 'really-simple-ssl'),
			'disabled' => false,
			'button_text' => __( "Test notifications", "really-simple-ssl" ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
					'send_notifications_email' => 1,
				]
			],
		],
		[
			'id'    => 'vulnerabilities-overview',
			'menu_id' => 'vulnerabilities_notifications',
			'group_id' => 'vulnerabilities_overview',
			'type' => 'vulnerabilitiestable',

			'label' => __('Vulnerabilities Overview', 'really-simple-ssl'),
			'disabled' => false,
			'default' => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => 1,
				]
			],
			'columns'          => [
				[
					'id'       => 'component',
					'name'     => __('Component', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'Name',
					'width'    => '55%',
					'searchable' => true,
				],
				[
					'id'       => 'date',
					'name'     => __('Date', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'date',
					'width'    => '15%',
				],
				[
					'id'       => 'risk',
					'name'     => __('Risk', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'risk_name',
					'width'    => '10%',
					'searchable' => true,
				],
				[
					'id'       => 'action',
					'name'     => __('', 'really-simple-ssl'),
					'sortable' => false,
					'column'   => 'vulnerability_action',
					'width'   => '20%',
				]
			]
		],
		[
			'id'               => 'vulnerabilities_measures',
			'menu_id'          => 'vulnerabilities-measures-overview',
			'group_id'         => 'vulnerabilities_measures',
			'type'             => 'riskcomponent',
			'options'          => [
				'*' => __('None', 'really-simple-ssl'),
				'l' => __('Low-risk', 'really-simple-ssl'),
				'm' => __('Medium-risk', 'really-simple-ssl'),
				'h' => __('High-risk', 'really-simple-ssl'),
				'c' => __('Critical', 'really-simple-ssl'),
			],
			'react_conditions' => [
				'relation' => 'AND',
				[
					'measures_enabled' => true,
				]
			],
			'disabled'         => false,
			'default'          => false,
			'columns'          => [
				[
					'name'     => __( 'Action', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'name',
					'width'    => '25%',
				],
				[
					'name'     => __( 'Risk', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'riskSelection',
					'width'         => '20%',
				],
				[
					'name'     => __( 'Description', 'really-simple-ssl' ),
					'sortable' => false,
					'column'   => 'description',
					'type'   => 'text',
					'minWidth'  => '300px',
				],
				[]
			],
		],
		[
			'id'       => 'measures_enabled',
			'menu_id'  => 'vulnerabilities_measures',
			'group_id' => 'vulnerabilities_measures',
			'type'     => 'checkbox',
			'label'    => __("I have read and understood the risks to intervene with these measures.","really-simple-ssl"),
			'comment' => '<a href="https://really-simple-ssl.com/instructions/about-vulnerabilities#measures" target="_blank" rel="noopener noreferrer">'.__("Read more", "really-simple-ssl") .'</a>',
			'disabled' => false,
			'default'  => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'enable_vulnerability_scanner' => true,
				]
			],
		],
		[
			'id'       => 'enforce_password_security_enabled',
			'menu_id'  => 'password_security',
			'group_id' => 'password_security_passwords',
			'type'     => 'checkbox',
			'label'    => __( "Enforce strong passwords", "really-simple-ssl" ),
			'help'     => [
				'label' => 'default',
				'url'   => 'https://really-simple-ssl.com/instructions/password-security/?mtm_campaign=definition&mtm_source=free',
				'title' => __( "Enforce Strong Passwords", 'really-simple-ssl' ),
				'text'  => __( 'Improve the default WordPress password strength check. You can also enforce frequent password changes for user roles.', 'really-simple-ssl' ).' '.__('They might be misused if you don’t actively tell the browser to disable these features.', 'really-simple-ssl' ),
			],
			'disabled' => false,
			'default'  => 'disabled',
			'tooltip'  => __( "This adds extra requirements for strong passwords for new users and updated passwords.",
				'really-simple-ssl' ),
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
				'6' => __( "8 hours (recommended)", "really-simple-ssl" ),
				'48' => __( "48 hours", "really-simple-ssl" ),
			],
			'label'    => __( "Let login cookie expire after", "really-simple-ssl" ),
		],
	];

	$fields = apply_filters( 'rsssl_fields', $fields );
	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$stored_options = get_site_option( 'rsssl_options', [] );
	} else {
		$stored_options = get_option( 'rsssl_options', [] );
	}

	foreach ( $fields as $key => $field ) {
		$field = wp_parse_args( $field, [ 'default' => '', 'id' => false, 'visible' => true, 'disabled' => false, 'recommended' => false ] );
		//handle server side conditions
		//but not if outside our settings pages
		if ( rsssl_is_logged_in_rest() && isset( $field['server_conditions'] ) ) {
			if ( ! rsssl_conditions_apply( $field['server_conditions'] ) ) {
				unset( $fields[ $key ] );
				continue;
			}
		}
		if ( $load_values ) {

			$value          = rsssl_sanitize_field( rsssl_get_option( $field['id'], $field['default'] ), $field['type'], $field['id'] );
			$field['never_saved'] = !array_key_exists( $field['id'], $stored_options );
			$field['value'] = apply_filters( 'rsssl_field_value_' . $field['id'], $value, $field );
			$fields[ $key ] = apply_filters( 'rsssl_field', $field, $field['id'] );
		}
	}

	$fields = apply_filters( 'rsssl_fields_values', $fields );

	return array_values( $fields );
}
