<?php
defined( 'ABSPATH' ) or die( );

/*
 * condition: if a question should be dynamically shown or hidden, depending on another answer. Use NOT answer to hide if not answer.
 * callback_condition: if should be shown or hidden based on an answer in another screen.
 * callback calls action rsssl_$page_$callback
 * required: required field
* */




$this->fields = $this->fields + array(
//		'system_status' => array(
//			'step'        => 1,
//			'section'     => 1,
//			'source'      => 'lets-encrypt',
//			'callback'    => 'system-status.php',
//		),





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
