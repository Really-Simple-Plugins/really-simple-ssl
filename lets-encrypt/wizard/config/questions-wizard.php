<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

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
            'default'     => '',
            'placeholder' => __( "example.com", 'really-simple-ssl' ),
            'label'       => __( "Your domain", 'really-simple-ssl' ),
            'required'    => true,
        ),

        'include_www' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'checkbox',
            'default'     => '',
            'label'       => __( "Include www domain", 'really-simple-ssl' ),
        ),

        'email_address'      => array(
            'step'      => 1,
            'section'   => 1,
            'source'    => 'lets-encrypt',
            'type'      => 'email',
            'default'   => '',
            'tooltip'   => __( "Your email address will be obfuscated on the front-end to prevent spidering.",
                'really-simple-ssl' ),
            'label'     => __( "Your e-mail address", 'really-simple-ssl' ),
        ),

        'accept_le_terms' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'checkbox',
            'default'     => '',
            'label'       => __( "Terms & Conditions", 'really-simple-ssl' ),
        ),

        'instructions' => array(
            'step'        => 1,
            'section'     => 2,
            'source'      => 'lets-encrypt',
            'type'        => 'checkbox',
            'default'     => '',
            'label'       => __( "Accept terms", 'really-simple-ssl' ),
        ),
    );

// Questions - Content

$this->fields = $this->fields + array(
        // constante zoeken + callback
        'verification' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Are you running a webshop?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),
    );

// End of Questions
$this->fields = $this->fields + array(
        'installation' => array(
            'step'     => 3,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'callback' => 'rsssl_add_pages',
            'label'    => '',
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