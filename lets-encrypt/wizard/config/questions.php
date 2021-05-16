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
			'option_text' => __("I agree to the Terms & Conditions", 'really-simple-ssl'),
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
	        'callback_condition' => 'rsssl_is_other',
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
			)
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
			'comment'     => sprintf(__("You can find your api key %shere%s.","really-simple-ssl"),'<a target="_blank" href="https://platform.cloudways.com/api">','</a>'),
			'condition' => array(
				'other_host_type' => 'cloudways'
			)
		),

        'cpanel_host' => array(
            'step'        => 2,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => '',
            'label'       => __( "CPanel host", 'really-simple-ssl' ),
            'required'    => true,
            'disabled'    => false,
	        'callback_condition' => 'rsssl_cpanel_api_supported'
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
	        'callback_condition' => 'rsssl_cpanel_api_supported'
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
	        'callback_condition' => 'rsssl_cpanel_api_supported'
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
		),

        'directories' => array(
	        'step'        => 3,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'directories.php',
	        'help'     => __('To make sure you have added everything correctly, view this example of these folders included in the root of a WordPress installation.', 'really-simple-ssl' ) . rsssl_read_more( 'https://complianz.io/what-is-force-majeure/' ),
	        'callback_condition' => 'rsssl_do_local_lets_encrypt_install'
        ),

        'generation' => array(
	        'step'        => 4,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'generation.php',
	        'callback_condition' => 'rsssl_do_local_lets_encrypt_install'
        ),

        'installation' => array(
	        'step'        => 5,
	        'section'     => 1,
	        'source'      => 'lets-encrypt',
	        'callback'    => 'installation.php',
        ),

        'activate_ssl' => array(
	        'step'     => 6,
	        'source'   => 'lets-encrypt',
	        'callback' => 'activate.php',
        ),
    );
