<?php

function rsssl_le_steps(){
	$steps =
		[
			[
				"id"       => "system-status",
				"title"    => __( "System status", 'really-simple-ssl' ),
			],
			[
				"id"    => "domain",
				"title" => __( "General Settings", 'really-simple-ssl' ),
			],

			[
				"id"       => "directories",
				"title"    => __( "Directories", 'really-simple-ssl' ),
			],
			[
				"id"    => "dns-verification",
				"title" => __( "DNS verification", 'really-simple-ssl' ),
			],
			[
				"id"    => "generation",
				"title" => __( "Generation", 'really-simple-ssl' ),
			],
			[
				"id"    => "installation",
				"title" => __( "Installation", 'really-simple-ssl' ),
			],
	];

	return $steps;
}

/**
 * Let's Encrypt
 */
add_filter("rsssl_fields", "rsssl_le_add_fields");
function rsssl_le_add_fields($fields) {

	$fields =  array_merge($fields,  [
			[
				'id'      => 'system-status',
				'menu_id' => 'le-system-status',
				'group_id' => 'le-system-status',
				"intro"   => __( "Detected status of your setup.", "really-simple-ssl" ),
				'type'    => 'letsencrypt',
				'default' => false,
				'actions' => [
					[
						'description' => __( "Checking SSL certificate...", "really-simple-ssl" ),
						'action'      => 'certificate_status',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking if CURL is available...", "really-simple-ssl" ),
						'action'      => 'curl_exists',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking server software...", "really-simple-ssl" ),
						'action'      => 'server_software',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking alias domain...", "really-simple-ssl" ),
						'action'      => 'alias_domain_available',
						'attempts'    => 3,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking for website configuration...", "really-simple-ssl" ),
						'action'      => 'check_domain',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
				],
			],
			[
				'id' => 'verification_type',
				'menu_id' => 'le-general',
				'group_id' => 'le-general',
				'type' =>  'hidden',
			],
			[
				'id'       => 'email_address',
				'menu_id'  => 'le-general',
				'group_id'  => 'le-general',
				'type'     => 'email',
				'label'    => __( "Email address", 'really-simple-ssl' ),
				'help'     => [
					'label' => 'default',
					'title' => __( "Email address", "really-simple-ssl" ),
					'text'  => __( "This email address is used to create a Let's Encrypt account. This is also where you will receive renewal notifications.", 'really-simple-ssl' ),
				],
				'default'  => '',
				'required' => true,
			],
			[
				'id'       => 'accept_le_terms',
				'menu_id'  => 'le-general',
				'group_id'  => 'le-general',
				'type'     => 'checkbox',
				'default'  => false,
				'required' => true,
				'label'    => __( 'I agree to the Terms & Conditions from Let\'s Encrypt.','really-simple-ssl'),
				'comment'    => '<a target="_blank" href="https://letsencrypt.org/documents/LE-SA-v1.2-November-15-2017.pdf">'.__('Terms & Conditions', "really-simple-ssl" ).'</a>',
			],
			[
				'id'      => 'disable_ocsp',
				'menu_id' => 'le-general',
				'group_id' => 'le-general',
				'required'=> false,
				'type'    => 'checkbox',
				'default' => false,
				'help' => [
					'label' => 'default',
					'url'   => 'https://really-simple-ssl.com/ocsp-stapling',
					'title' => __( "Disable OCSP stapling", "really-simple-ssl" ),
					'text'  => __( "OCSP stapling is configured as enabled by default. You can disable this option if this is not supported by your hosting provider.", "really-simple-ssl" ),
				],
				'label'   => __( "Disable OCSP stapling", 'really-simple-ssl' ),
			],
			[
				'id'       => 'domain',
				'menu_id'  => 'le-general',
				'group_id'  => 'le-general',
				'type'     => 'text',
				'default'  => rsssl_get_domain(),
				'label'    => __( "Domain", 'really-simple-ssl' ),

				'required' => false,
				'disabled' => true,
			],
			[
				'id'                => 'include_alias',
				'menu_id'           => 'le-general',
				'group_id'           => 'le-general',
				'type'              => 'checkbox',
				'default'           => '',
				'label'    => __( "Include alias", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => __( "Include alias", "really-simple-ssl" ),
					'text'  => __( "This will include both the www. and non-www. version of your domain.", "really-simple-ssl" ) . ' '
					           . __( "You should have the www domain pointed to the same website as the non-www domain.", 'really-simple-ssl' ),
				],
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_subdomain()'                  => false,
						'rsssl_wildcard_certificate_required()' => false,
					]
				],
			],
			[
				'id'       => 'other_host_type',
				'menu_id'  => 'le-hosting',
				'group_id'  => 'le-hosting',
				'type'     => 'host',
				//options loaded in data store
				'help'     => [
					'label' => 'default',
					'title' => __( "Hosting provider", "really-simple-ssl" ),
					'text'  => __( "By selecting your hosting provider we can tell you if your hosting provider already supports free SSL, and/or where you can activate it.", "really-simple-ssl" )
					           . "&nbsp;" .
					           sprintf( __( "If your hosting provider is not listed, and there's an SSL activation/installation link, please let us %sknow%s.", "really-simple-ssl" ),
						           '<a target="_blank" href="https://really-simple-ssl.com/install-ssl-certificate/#hostingdetails">', '</a>' ),
				],
				'default'  => false,
				'label'    => __( "Hosting provider", 'really-simple-ssl' ),
				'required' => false,
				'disabled' => false,
			],
			[
				'id'                => 'cpanel_host',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "CPanel host", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => __( "CPanel host", "really-simple-ssl" ),
					'text'  => __( "The URL you use to access your cPanel dashboard. Ends on :2083.", 'really-simple-ssl' ),
				],
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_cpanel()'            => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'cpanel_username',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "CPanel username", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_cpanel_api_supported()' => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'cpanel_password',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'password',
				'default'           => '',
				'label'             => __( "CPanel password", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_cpanel_api_supported()' => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'directadmin_host',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "DirectAdmin host", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => __( "Direct Admin URL", "really-simple-ssl" ),
					'text'  => __( "The URL you use to access your DirectAdmin dashboard. Ends on :2222.", 'really-simple-ssl' ),
				],
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_directadmin()'       => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'directadmin_username',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "DirectAdmin username", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_directadmin()'       => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'directadmin_password',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'password',
				'default'           => '',
				'label'             => __( "DirectAdmin password", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_directadmin()'       => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'cloudways_user_email',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'placeholder'       => 'email@email.com',
				'label'             => __( "CloudWays user email", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'other_host_type' => 'cloudways',
					]
				],
			],
			[
				'id'                => 'cloudways_api_key',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'password',
				'default'           => '',
				'label'             => __( "CloudWays API key", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'help'              => [
					'label' => 'default',
					'title' => __( "CloudWays API key", "really-simple-ssl" ),
					'text'  => sprintf( __( "You can find your api key %shere%s (make sure you're logged in with your main account).", "really-simple-ssl" ),
						'<a target="_blank" href="https://platform.cloudways.com/api">', '</a>' ),
				],
				'server_conditions' => [
					'relation' => 'AND',
					[
						'other_host_type' => 'cloudways',
					]
				],
			],
			[
				'id'                => 'plesk_host',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "Plesk host", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => __( "Plesk admin URL", "really-simple-ssl" ),
					'text'  => __( "The URL you use to access your Plesk dashboard. Ends on :8443.", 'really-simple-ssl' ),
				],
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_plesk()'             => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'plesk_username',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'text',
				'default'           => '',
				'label'             => __( "Plesk username", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => __( "Plesk username and password", "really-simple-ssl" ),
					'text'  => sprintf( __( "You can find your Plesk username and password in %s", 'really-simple-ssl' ), 'https://{your-plesk-host-name}:8443/smb/my-profile' ),
				],
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_plesk()'             => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'plesk_password',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'password',
				'default'           => '',
				'label'             => __( "Plesk password", 'really-simple-ssl' ),
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_is_plesk()'             => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                => 'store_credentials',
				'menu_id'           => 'le-hosting',
				'group_id'           => 'le-hosting',
				'type'              => 'checkbox',
				'default'           => '',
				'label'             => __( "Do you want to store these credentials for renewal purposes?", 'really-simple-ssl' ),
				'help'              => [
					'label' => 'default',
					'title' => 'Storing credentials',
					'text'  => __( "Store for renewal purposes. If not stored, renewal may need to be done manually.", 'really-simple-ssl' ),
				],
				'required'          => false,
				'disabled'          => false,
				'server_conditions' => [
					'relation' => 'AND',
					[
						'rsssl_uses_known_dashboard()' => true,
						'rsssl_activated_by_default()' => false,
						'rsssl_activation_required()'  => false,
						'rsssl_paid_only()'            => false,
					]
				],
			],
			[
				'id'                 => 'directories',
				'menu_id'            => 'le-directories',
				'group_id'            => 'le-directories',
				'condition_action'   => 'hide',
				'type'               => 'letsencrypt',
				'actions'            => [
					[
						'description' => __( "Checking host...", "really-simple-ssl" ),
						'action'      => 'check_host',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking challenge directory...", "really-simple-ssl" ),
						'action'      => 'check_challenge_directory',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking key directory...", "really-simple-ssl" ),
						'action'      => 'check_key_directory',
						'attempts'    => 2,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking certs directory...", "really-simple-ssl" ),
						'action'      => 'check_certs_directory',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __( "Checking permissions...", "really-simple-ssl" ),
						'action'      => 'check_writing_permissions',
						'attempts'    => 1,
						'status'      => 'inactive',
					],

					[
						'description' => __( "Checking challenge directory reachable over http...", "really-simple-ssl" ),
						'action'      => 'challenge_directory_reachable',
						'attempts'    => 1,
						'status'      => 'inactive',
					],
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!verification_type' => 'dns',
					]
				],
			],
			[
				'id'       => 'dns-verification',
				'menu_id'  => 'le-dns-verification',
				'group_id' => 'le-dns-verification',
				'type'     => 'letsencrypt',
				'condition_action'   => 'hide',
				'actions' => [
					[
						'description' => __("Creating account...", "really-simple-ssl"),
						'action'=> 'get_account',
						'attempts' => 5,
						'status'      => 'inactive',
					],
					[
						'description' => __("Retrieving DNS verification token...", "really-simple-ssl"),
						'action'=> 'get_dns_token',
						'attempts' => 5,
						'status'      => 'inactive',
					],
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'verification_type' => 'dns',
					]
				],
			],
			[
				'id'                 => 'generation',
				'menu_id'            => 'le-generation',
				'group_id'            => 'le-generation',
				'type'               => 'letsencrypt',
//				'server_conditions' => [
//					'relation' => 'AND',
//					[
//						'rsssl_do_local_lets_encrypt_generation' => true,
//					]
//				],
				'actions' => [
					[
						'description' => __("Checking if Terms & Conditions are accepted...", "really-simple-ssl"),
						'action'=> 'terms_accepted',
						'attempts' => 1,
						'status'      => 'inactive',
					],
					[
						'description' => __("Creating account...", "really-simple-ssl"),
						'action'=> 'get_account',
						'attempts' => 5,
						'status'      => 'inactive',
					],
					[
						'description' => __("Generating SSL certificate...", "really-simple-ssl"),
						'action'=> 'create_bundle_or_renew',
						'attempts' => 5,
						'status'      => 'inactive',
					],
				],
			],
			[
				'id'       => 'installation',
				'menu_id'  => 'le-installation',
				'group_id'  => 'le-installation',
				'type'     => 'letsencrypt',
				'actions' => [
					[
						'description' => __("Searching for link to SSL installation page on your server...", "really-simple-ssl"),
						'action'=> 'search_ssl_installation_url',
						'attempts' => 1,
						'status'      => 'inactive',
					],
				],
			],
			[
				'id'       => 'activate_ssl',
				'menu_id'  => 'le-activate_ssl',
				'group_id'  => 'le-activate_ssl',
				'type'     => 'activate',
			],
		]);

	if ( is_multisite() ) {
		$index           = array_search( 'system-status', array_column( $fields, 'id' ) );
		$new_test        = [
			'description' => __( "Checking for subdomain setup...", "really-simple-ssl" ),
			'action'      => 'is_subdomain_setup',
			'attempts'    => 1,
			'status'      => 'inactive',
		];
		$current_tests   = $fields[ $index ]['actions'];
		$current_tests[] = $new_test;
		$fields[ $index ]['actions'] = $current_tests;
	}

	return $fields;
}
