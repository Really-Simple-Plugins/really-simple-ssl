<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
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
				'tooltip'  => __( "Verify your email address to get the most out of Really Simple Security.", 'really-simple-ssl' ),
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
				'id'           => 'enabled_captcha_provider',
				'menu_id'      => 'general',
				'group_id'     => 'general_captcha',
				'type'         => 'select',
				'options'      => [
					'none'      => __( "Choose your provider", "really-simple-ssl" ),
					'recaptcha' => __( "reCaptcha v2", "really-simple-ssl" ),
					'hcaptcha'  => __( "hCaptcha", "really-simple-ssl" ),
				],
				'label'        => __( "Captcha provider", 'really-simple-ssl' ),
				'disabled'     => false,
				'default'      => 'none',
				'required' => false,
			],
			[
				'id'      => 'captcha_fully_enabled',
				'menu_id' => 'general',
				'group_id' => 'general_captcha',
				'type'    => 'hidden',
				'label'   => '',
				'default' => false,
			],
			[
				'id'       => 'recaptcha_site_key',
				'menu_id'  => 'general',
				'group_id' => 'general_captcha',
				'type'     => 'captcha_key',
				'label'    => __( "reCaptcha site key", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
				'required' => true,
				'visible'   => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'enabled_captcha_provider' => 'recaptcha',
					]
				],
			],
			[
				'id'       => 'recaptcha_secret_key',
				'menu_id'  => 'general',
				'group_id' => 'general_captcha',
				'type'     => 'captcha_key',
				'label'    => __( "reCaptcha secret key", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
				'required' => true,
				'visible'   => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'enabled_captcha_provider' => 'recaptcha',
					]
				],
			],
			[
				'id'       => 'hcaptcha_site_key',
				'menu_id'  => 'general',
				'group_id' => 'general_captcha',
				'type'     => 'captcha_key',
				'label'    => __( "hCaptcha site key", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
				'required' => true,
				'visible'   => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'enabled_captcha_provider' => 'hcaptcha',
					]
				],
			],
			[
				'id'       => 'hcaptcha_secret_key',
				'menu_id'  => 'general',
				'group_id' => 'general_captcha',
				'type'     => 'captcha_key',
				'label'    => __( "hCaptcha secret key", 'really-simple-ssl'),
				'required' => true,
				'disabled' => false,
				'default'  => false,
				'visible'   => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'enabled_captcha_provider' => 'hcaptcha',
					]
				],
			],
			[
				'id'      => 'captcha_verified',
				'menu_id' => 'general',
				'group_id' => 'general_captcha',
				'type'    => 'captcha',
				'info'     => __( "Captcha has not yet been verified, you need to complete the process of a Captcha to verify it's availability.", 'really-simple-ssl' ),
				'label'   => '',
				'default' => false,
			],

			[
				'id'       => 'premium_support',
				'menu_id'  => 'general',
				'group_id' => 'support',
				'type'     => 'support',
				'label'    => __("Premium Support", 'really-simple-ssl'),
				'disabled' => false,
				'default'  => false,
			],

			[
				'id'                   => 'do_not_edit_htaccess', //field is removed if not enabled
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

		]
	);
}, 100 );
