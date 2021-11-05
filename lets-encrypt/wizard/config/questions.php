<?php
defined( 'ABSPATH' ) or die( );

/*
 * condition: if a question should be dynamically shown or hidden, depending on another answer. Use NOT answer to hide if not answer.
 * callback_condition: if should be shown or hidden based on an answer in another screen.
 * callback roept action rsssl_$page_$callback aan
 * required: verplicht veld.
 * help: helptext die achter het veld getoond wordt.

                "fieldname" => '',
                "type" => 'text',
                "required" => false,
                'default' => '',
                'label' => '',
                'table' => false,
                'callback_condition' => false,
                'condition' => false,
                'callback' => false,
                'placeholder' => '',
                'optional' => false,

* */

$this->fields = $this->fields + array(
		'system_status' => array(
			'step'        => 1,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'callback'    => 'system-status.php',
		),

		'email_address'      => array(
			'step'      => 2,
			'section'   => 1,
			'source'    => 'lets-encrypt',
			'type'      => 'email',
			'default'   => get_option('admin_email'),
			'tooltip'   => __( "This email address will used to create a Let's Encrypt account. This is also where you will receive renewal notifications.", 'really-simple-ssl' ),
			'tooltip-position' => 'title',
			'label'     => __( "Email address", 'really-simple-ssl' ),
			'sublabel'  => __("This field is prefilled based on your configuration", 'really-simple-ssl'),
			'required'  => true,
		),

		'accept_le_terms' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'checkbox',
			'default'     => '',
			'required'    => true,
			'title'       => __('Terms & Conditions',"really-simple-ssl"),
			'option_text' => sprintf(__("I agree to the Let's Encrypt %sTerms & Conditions%s", 'really-simple-ssl'),'<a target="_blank" href="https://letsencrypt.org/documents/LE-SA-v1.2-November-15-2017.pdf">','</a>'),
		),
		'disable_ocsp' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'checkbox',
			'default'     => '',
			'help'      => __( "OCSP stapling should be enabled by default. You can disable if this is not supported by your hosting provider.","really-simple-ssl").rsssl_read_more('https://really-simple-ssl.com/ocsp-stapling'),
			'title'       => __('OCSP Stapling',"really-simple-ssl"),
			'option_text' => __("Disable OCSP Stapling", 'really-simple-ssl'),
		),

        'domain' => array(
            'step'        => 2,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => rsssl_get_domain(),
            'label'       => __( "Domain", 'really-simple-ssl' ),
            'sublabel'    => __("This field is prefilled based on your configuration", 'really-simple-ssl'),
            'required'    => false,
            'disabled'    => true,
        ),

        'include_alias' => array(
	        'step'        => 2,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'type'        => 'checkbox',
	        'default'     => '',
	        'tooltip'   => __( "This will include both the www. and non-www. version of your domain.", "really-simple-ssl").' '.__("You should have the www domain pointed to the same website as the non-www domain.", 'really-simple-ssl' ),
	        'tooltip-position' => 'after',
	        'option_text' => __("Include alias domain too?", 'really-simple-ssl'),
	        'callback_condition' => array(
	        	'NOT rsssl_is_subdomain',
	        	'NOT rsssl_wildcard_certificate_required',
	        )
        ),

        'other_host_type' => array(
	        'step'        => 2,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'type'        => 'select',
	        'tooltip'   => __( "By selecting your hosting provider we can tell you if your hosting provider already supports free SSL, and how you can activate it.", "really-simple-ssl"),
	        'options'     => $this->supported_hosts,
	        'help'      => __( "By selecting your hosting provider we can tell you if your hosting provider already supports free SSL, and/or where you can activate it.","really-simple-ssl")."&nbsp;".
	                       sprintf(__("If your hosting provider is not listed, and there's an SSL activation/installation link, please let us %sknow%s.","really-simple-ssl"),'<a target="_blank" href="https://really-simple-ssl.com/install-ssl-certificate/#hostingdetails">','</a>'),
	        'default'     => false,
	        'label'       => __( "Hosting provider", 'really-simple-ssl' ),
	        'required'    => true,
	        'disabled'    => false,
        ),

        'cpanel_host' => array(
            'step'        => 2,
            'section'     => 2,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => '',
            'label'       => __( "CPanel host", 'really-simple-ssl' ),
            'help'       => __( "The URL you use to access your cPanel dashboard. Ends on :2083.", 'really-simple-ssl' ),
            'required'    => false,
            'disabled'    => false,
	        'callback_condition' => array(
	        	'rsssl_is_cpanel',
		        'NOT rsssl_activated_by_default',
		        'NOT rsssl_activation_required',
		        'NOT rsssl_paid_only',
	        )
        ),

        'cpanel_username' => array(
	        'step'        => 2,
	        'section'     => 2,
	        'source'      => 'lets-encrypt',
	        'type'        => 'text',
	        'default'     => '',
	        'label'       => __( "CPanel username", 'really-simple-ssl' ),
	        'required'    => false,
	        'disabled'    => false,
	        'callback_condition' => array(
		        'rsssl_cpanel_api_supported',
		        'NOT rsssl_activated_by_default',
		        'NOT rsssl_activation_required',
		        'NOT rsssl_paid_only',
	        )
        ),

        'cpanel_password' => array(
	        'step'        => 2,
	        'section'     => 2,
	        'source'      => 'lets-encrypt',
	        'type'        => 'password',
	        'default'     => '',
	        'label'       => __( "CPanel password", 'really-simple-ssl' ),
	        'required'    => false,
	        'disabled'    => false,
	        'callback_condition' => array(
		        'rsssl_cpanel_api_supported',
		        'NOT rsssl_activated_by_default',
		        'NOT rsssl_activation_required',
		        'NOT rsssl_paid_only',
	        )        ),

		'directadmin_host' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "DirectAdmin host", 'really-simple-ssl' ),
			'help'       => __( "The URL you use to access your DirectAdmin dashboard. Ends on :2222.", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
	        'callback_condition' => array(
				'rsssl_is_directadmin',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),

		'directadmin_username' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "DirectAdmin username", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_is_directadmin',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),

		'directadmin_password' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "DirectAdmin password", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_is_directadmin',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),

		'cloudways_user_email' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'placeholder' => 'email@email.com',
			'label'       => __( "CloudWays user email", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'other_host_type' => 'cloudways'
			),
		),
		'cloudways_api_key' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "CloudWays api key", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'comment'     => sprintf(__("You can find your api key %shere%s (make sure you're logged in with your main account).","really-simple-ssl"),'<a target="_blank" href="https://platform.cloudways.com/api">','</a>'),
			'callback_condition' => array(
				'other_host_type' => 'cloudways'
			)
		),

		'plesk_host' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk host", 'really-simple-ssl' ),
			'help'       => __( "The URL you use to access your Plesk dashboard. Ends on :8443.", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_is_plesk',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),
		'plesk_username' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk username", 'really-simple-ssl' ),
			'help'       => sprintf(__( "You can find your Plesk username and password in %s", 'really-simple-ssl' ),'https://{your-plesk-host-name}:8443/smb/my-profile'),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_is_plesk',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),

		'plesk_password' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "Plesk password", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_is_plesk',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			)
		),

		'store_credentials' => array(
			'step'        => 2,
			'section'     => 2,
			'source'      => 'lets-encrypt',
			'type'        => 'checkbox',
			'default'     => '',
			'title'       => __( "Credentials storage", 'really-simple-ssl' ),
			'option_text'       => __( "Store for renewal purposes. If not stored, renewal may need to be done manually.", 'really-simple-ssl' ),
			'required'    => false,
			'disabled'    => false,
			'callback_condition' => array(
				'rsssl_uses_known_dashboard',
				'NOT rsssl_activated_by_default',
				'NOT rsssl_activation_required',
				'NOT rsssl_paid_only',
			),
		),

        'directories' => array(
	        'step'        => 3,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'directories.php',
	        'callback_condition' => 'rsssl_do_local_lets_encrypt_generation'
        ),

		'dns-verification' => array(
			'step'        => 4,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'callback'    => 'dns-verification.php',
			'callback_condition' => 'rsssl_dns_verification_required'
		),

        'generation' => array(
	        'step'        => 5,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'generation.php',
	        'callback_condition' => 'rsssl_do_local_lets_encrypt_generation'
        ),

        'installation' => array(
	        'step'        => 6,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'installation.php',
        ),

        'activate_ssl' => array(
	        'step'     => 7,
	        'source'   => 'lets-encrypt',
	        'callback' => 'activate.php',
        ),
    );
