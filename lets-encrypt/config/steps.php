<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

$this->steps = apply_filters('rsssl_steps',array(
    'lets-encrypt' =>
        array(
            1 => array(
                "id"    => "company",
                "title" => __( "General", 'really-simple-ssl' ),
                'intro' => '<h1>'.__('Terms & conditions', 'really-simple-ssl').'</h1><p>'.
                    sprintf(__('We have tried to make our Wizard as simple and fast as possible. Although these questions are all necessary, if there’s any way you think we can improve the plugin, please let us %sknow%s!', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/contact">', '</a>').
                    sprintf(__(' Please note that you can always save and finish the wizard later, use our %sdocumentation%s for additional information or log a %ssupport ticket%s if you need our assistance.', 'really-simple-ssl'),'<a target="_blank" href="https://rsssl.io/docs/lets-encrypt">', '</a>','<a target="_blank" href="https://rsssl.io/support">', '</a>').'</p>',
            ),

            2 => array(
                "title"    => __( "Questions", 'really-simple-ssl' ),
                "id"       => "questions",
                'sections' => array(
                    1 => array(
                        'title' => __( 'Content', 'really-simple-ssl' ),
                        'intro' => __( 'These questions will concern the content presented on your website and specific functionalities that might need to be included in the Terms & conditions.', 'really-simple-ssl' ). rsssl_read_more( 'https://rsssl.io/docs/lets-encrypt/' ),
                    ),
                    2 => array(
                        'title' => __( 'Communication', 'really-simple-ssl' ),
                        'intro' => __( 'These questions will explicitly explain your efforts in communicating with your customers or visitors regarding the services you provide.', 'really-simple-ssl'),

                    ),

                    3 => array(
                        'title' => __( 'Liability', 'really-simple-ssl' ),
                        'intro' => __( 'Based on earlier answers you can now choose to limit liability if needed.', 'really-simple-ssl' ). rsssl_read_more( 'https://rsssl.io/docs/lets-encrypt/' ),

                    ),

                    4 => array(
                        'title' => __( 'Copyright', 'really-simple-ssl' ),
                        'intro' => __( 'Creative Commons (CC) is an American non-profit organization devoted to expanding the range of creative works available for others to build upon legally and to share.', 'really-simple-ssl' ),
                    ),

                    5 => array(
                        'title' => __( 'Returns', 'really-simple-ssl' ),
                        'intro' => __( 'If you offer returns of goods or the withdrawal of services you can specify the terms below.', 'really-simple-ssl' ). rsssl_read_more( 'https://rsssl.io/docs/lets-encrypt/' ),
                    ),
                ),
            ),

            3    => array(
                "id"    => "menu",
                "title" => __( "Document", 'really-simple-ssl' ),
                'intro' =>
                    '<h1>' . __( "Get ready to finish your configuration.", 'really-simple-ssl' ) . '</h1>' .
                    '<p>'
                    . __( "Generate the Terms & conditions, then you can add them to your menu directly or do it manually after the wizard is finished.", 'really-simple-ssl' ) . '</p>',
                'sections' => array(
                    1 => array(
                        'title' => __( 'Create document', 'really-simple-ssl' ),
                    ),
                    2 => array(
                        'title' => __( 'Link to menu', 'really-simple-ssl' ),
                    ),
                ),

            ),
            4  => array(
                "title" => __( "Finish", 'really-simple-ssl' ),
            ),
        ),
));