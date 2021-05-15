<?php
defined( 'ABSPATH' ) or die( "you do not have accesss to this page!" );

$this->steps = apply_filters('rsssl_steps',array(
    'lets-encrypt' =>
        array(
	        1 => array(
		        "id"       => "system-status",
		        "title"    => __( "System Status", 'really-simple-ssl' ),
		        "intro"    => __( "Detected status of your setup.", "really-simple-ssl" ),
				'actions' => array(
		        	array(
		        		'description' => __("Checking PHP version...", "really-simple-ssl"),
				        'action'=> 'rsssl_php_requirement_met',
				        'attempts' => 1,
			        ),
			        array(
				        'description' => __("Checking SSL certificate...", "really-simple-ssl"),
				        'action'=> 'certificate_status',
				        'attempts' => 1,
			        ),
			        array(
				        'description' => __("Checking server software...", "really-simple-ssl"),
				        'action'=> 'server_software',
				        'attempts' => 1,
			        ),
			        array(
				        'description' => __("Checking for localhost installation...", "really-simple-ssl"),
				        'action'=> 'localhost_used',
				        'attempts' => 1,
			        ),
		        ),
	        ),
            2 => array(
                "id"    => "domain",
                "title" => __( "Domain", 'really-simple-ssl' ),
                'intro' => '<h1>'.__('Terms & conditions', 'really-simple-ssl').'</h1><p>'.
                    sprintf(__('We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/contact">', '</a>').
                    sprintf(__(' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/docs/lets-encrypt">', '</a>','<a target="_blank" href="https://rsssl.io/support">', '</a>').'</p>',
//                'sections' => array (
//                    1 => array(
//                        'title' => __( 'Information', 'really-simple-ssl' ),
//                        'intro' => __( 'Letʼs Encrypt is a free, automated and open certificate authority brought to you by the nonprofit Internet Security Research Group (ISRG).', 'really-simple-ssl' ). rsssl_read_more( 'https://rsssl.io/docs/lets-encrypt/wizard/' ),
//                    ),
//                    2 => array(
//                        'title' => __( 'Instructions', 'really-simple-ssl' ),
//                        'intro' => __( 'Below you will find the instructions for different hosting environments and configurations. If you start the process with the necessary instructions and credentials the next view steps will be done in no time.', 'really-simple-ssl'),
//
//                    ),
//                )
                'actions' => array(),
            ),

            3 => array(
                "id"       => "directories",
                "title"    => __( "Directories", 'really-simple-ssl' ),
                'actions' => array(
	                array(
		                'description' => __("Checking challenge directory...", "really-simple-ssl"),
		                'action'=> 'check_challenge_directory',
		                'attempts' => 1,
	                ),
	                array(
		                'description' => __("Checking key directory...", "really-simple-ssl"),
		                'action'=> 'check_key_directory',
		                'attempts' => 1,
	                ),
	                array(
		                'description' => __("Checking certs directory...", "really-simple-ssl"),
		                'action'=> 'check_certs_directory',
		                'attempts' => 1,
	                ),
	                array(
		                'description' => __("Checking permissions...", "really-simple-ssl"),
		                'action'=> 'check_writing_permissions',
		                'attempts' => 1,
	                ),
                ),
            ),
            4    => array(
	            "id"    => "generation",
	            "title" => __( "Generation", 'really-simple-ssl' ),
	            "intro" => __( "We will now generate your SSL Certificate", "really-simple-ssl" ),
	            'actions' => array(
		            array(
			            'description' => __("Creating account...", "really-simple-ssl"),
			            'action'=> 'get_account',
			            'attempts' => 5,
		            ),
		            array(
			            'description' => __("Generating SSL certificate...", "really-simple-ssl"),
			            'action'=> 'create_bundle_or_renew',
			            'attempts' => 5,
		            ),

	            ),
            ),
            5    => array(
                "id"    => "installation",
                "title" => __( "Installation", 'really-simple-ssl' ),
                'actions' => array(
	                array(
		                'description' => __("Searching for link to SSL installation page on your server...", "really-simple-ssl"),
		                'action'=> 'search_ssl_installation_url',
		                'attempts' => 1,
	                ),
                ),
            ),
            6  => array(
	            "id"    => "activation",
	            "title" => __( "Activate SSL", 'really-simple-ssl' ),
	            'actions' => array(),
            ),
        ),
));