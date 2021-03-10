<?php
defined( 'ABSPATH' ) or die( "you do not have accesss to this page!" );

$this->steps = apply_filters('rsssl_steps',array(
    'lets-encrypt' =>
        array(
            1 => array(
                "id"    => "domain",
                "title" => __( "Domain", 'really-simple-ssl' ),
                'intro' => '<h1>'.__('Terms & conditions', 'really-simple-ssl').'</h1><p>'.
                    sprintf(__('We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/contact">', '</a>').
                    sprintf(__(' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/docs/lets-encrypt">', '</a>','<a target="_blank" href="https://rsssl.io/support">', '</a>').'</p>',
                'sections' => array (
                    1 => array(
                        'title' => __( 'Information', 'really-simple-ssl' ),
                        'intro' => __( 'Letʼs Encrypt is a free, automated and open certificate authority brought to you by the nonprofit Internet Security Research Group (ISRG).We probably need some form of mention regarding terms, copyright etc.', 'really-simple-ssl' ). rsssl_read_more( 'https://rsssl.io/docs/lets-encrypt/wizard/' ),
                    ),
                    2 => array(
                        'title' => __( 'Instructions', 'really-simple-ssl' ),
                        'intro' => __( 'Below you will find the instructions for different hosting environments and configurations. If you start the process with the necessary instructions and credentials the next view steps will be done in no time.', 'really-simple-ssl'),

                    ),
                )
            ),

            2 => array(
                "id"       => "verification",
                "title"    => __( "Verification", 'really-simple-ssl' ),
            ),

            3    => array(
                "id"    => "installation",
                "title" => __( "Installation", 'really-simple-ssl' ),
            ),
            4  => array(
                "title" => __( "Activate SSL", 'really-simple-ssl' ),
            ),
        ),
));