<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

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

// General
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
			'tooltip'   => __( "Your email address will be obfuscated on the front-end to prevent spidering.",
				'really-simple-ssl' ),
			'tooltip-position' => 'title',
			'label'     => __( "Your e-mail address", 'really-simple-ssl' ),
			'sublabel'  => __("This field is prefilled based on your configuration", 'really-simple-ssl'),
			'required'  => true,
		),

		'accept_le_terms' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'checkbox',
			'default'     => '',
			'title'       => __('Terms & Conditions',"really-simple-ssl"),
			'option_text' => sprintf(__("I agree to the Let's Encrypt %sTerms & Conditions%s", 'really-simple-ssl'),'<a target="_blank" href="https://letsencrypt.org/documents/LE-SA-v1.2-November-15-2017.pdf">','</a>'),
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
	        'tooltip'   => __( "If your site is without www, it is recommended to add the www domain to your certificate as well (and vice versa).", "really-simple-ssl").' '.__("You should have the www domain pointed to the same website as the non-www domain.", 'really-simple-ssl' ),
	        'tooltip-position' => 'after',
	        'option_text' => __("Include alias domain too?", 'really-simple-ssl'),
	        'callback_condition' => 'NOT rsssl_is_subdomain',
        ),

        'other_host_type' => array(
	        'step'        => 2,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'type'        => 'select',
	        'options'     => $this->supported_hosts,
	        'default'     => false,
	        'label'       => __( "Hosting company", 'really-simple-ssl' ),
	        'required'    => true,
	        'disabled'    => false,
        ),

        'cpanel_host' => array(
            'step'        => 2,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => '',
            'label'       => __( "CPanel host", 'really-simple-ssl' ),
            'help'       => __( "The URL you use to access your cPanel dashboard. Ends on :2083.", 'really-simple-ssl' ),
            'required'    => true,
            'disabled'    => false,
	        'callback_condition' => 'rsssl_is_cpanel'
        ),

        'cpanel_username' => array(
	        'step'        => 2,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'type'        => 'text',
	        'default'     => '',
	        'label'       => __( "CPanel username", 'really-simple-ssl' ),
	        'required'    => true,
	        'disabled'    => false,
	        'callback_condition' => 'rsssl_cpanel_api_supported',
	        'condition' => array(
	        	'other_host_type' => 'NOT hostgator',
	        ),
        ),

        'cpanel_password' => array(
	        'step'        => 2,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'type'        => 'password',
	        'default'     => '',
	        'label'       => __( "CPanel password", 'really-simple-ssl' ),
	        'required'    => true,
	        'disabled'    => false,
	        'callback_condition' => 'rsssl_cpanel_api_supported',
	        'condition' => array(
		        'other_host_type' => 'NOT hostgator',
	        ),
        ),

		'cloudways_user_email' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'placeholder' => 'email@email.com',
			'label'       => __( "CloudWays user email", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'condition' => array(
				'other_host_type' => 'cloudways'
			),
		),
		'cloudways_api_key' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "CloudWays api key", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'comment'     => sprintf(__("You can find your api key %shere%s (make sure you're logged in with your main account).","really-simple-ssl"),'<a target="_blank" href="https://platform.cloudways.com/api">','</a>'),
			'condition' => array(
				'other_host_type' => 'cloudways'
			)
		),

		'plesk_host' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk host", 'really-simple-ssl' ),
			'help'       => __( "The URL you use to access your Plesk dashboard. Ends on :8443.", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'callback_condition' => 'rsssl_is_plesk',
		),
		'plesk_username' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'text',
			'default'     => '',
			'label'       => __( "Plesk username", 'really-simple-ssl' ),
			'help'       => sprintf(__( "You can find your Plesk username and password in %s", 'really-simple-ssl' ),'https://{your-plesk-host-name}:8443/smb/my-profile'),
			'required'    => true,
			'disabled'    => false,
			'callback_condition' => 'rsssl_is_plesk',
		),

		'plesk_password' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'password',
			'default'     => '',
			'label'       => __( "Plesk password", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'callback_condition' => 'rsssl_is_plesk',
		),

		'store_credentials' => array(
			'step'        => 2,
			'section'     => 1,
			'source'      => 'lets-encrypt',
			'type'        => 'checkbox',
			'default'     => '',
			'title'       => __( "Credentials storage", 'really-simple-ssl' ),
			'option_text'       => __( "Store for renewal purposes. If not stored, renewal may need to be done manually.", 'really-simple-ssl' ),
			'required'    => true,
			'disabled'    => false,
			'condition' => array(
				'other_host_type' => 'NOT cloudways'
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
