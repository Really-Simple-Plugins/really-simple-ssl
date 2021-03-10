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
        'domain' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => rsssl_get_non_www_domain(),
            'placeholder' => rsssl_get_non_www_domain(),
            'label'       => __( "Your domain", 'really-simple-ssl' ),
            'sublabel'  => __("This field is prefilled based on your configuration", 'really-simple-ssl'),
            'required'    => true,
        ),

        'include_www' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'checkbox',
            'default'     => '',
//            'label'       => __( "Include www domain", 'really-simple-ssl' ),
            'option_text' => __("Include www-prefixed version too?", 'really-simple-ssl'),
        ),

        'email_address'      => array(
            'step'      => 1,
            'section'   => 1,
            'source'    => 'lets-encrypt',
            'type'      => 'email',
            'default'   => get_option('admin_email'),
            'tooltip'   => __( "Your email address will be obfuscated on the front-end to prevent spidering.",
                'really-simple-ssl' ),
            'label'     => __( "Your e-mail address", 'really-simple-ssl' ),
            'sublabel'  => __("This field is prefilled based on your configuration", 'really-simple-ssl'),
            'required'  => true,
        ),

        'accept_le_terms' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'checkbox',
            'default'     => '',
            'title'       => 'Terms & Conditions',
//            'label'       => __( "Terms & Conditions", 'really-simple-ssl' ),
            'option_text' => __("I agree to the Terms & Conditions", 'really-simple-ssl'),
        ),

        'instructions' => array(
            'step'        => 1,
            'section'     => 2,
            'source'      => 'lets-encrypt',
            'label'       => '',
            'callback'    => 'add_instructions_page',
        ),
    );

// Questions - Content

$this->fields = $this->fields + array(
        // constante zoeken + callback
        'verification' => array(
            'step'        => 2,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'label'       => '',
            'callback'    => 'add_verification_page',
        ),
    );

// End of Questions
$this->fields = $this->fields + array(
        'installation' => array(
            'step'        => 3,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'label'       => '',
            'callback'    => 'add_installation_page',
        ),
    );


$this->fields = $this->fields + array(
        'activate_ssl' => array(
            'step'     => 4,
            'source'   => 'lets-encrypt',
            'callback' => 'last_step',
            'label'    => '',
        ),
    );