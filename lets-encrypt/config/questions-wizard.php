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

        'organisation_name' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'text',
            'default'     => '',
            'placeholder' => __( "Company or personal name", 'really-simple-ssl' ),
            'label'       => __( "Who is the owner of the website?", 'really-simple-ssl' ),
            'required'    => true,
        ),

        'address_company' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'placeholder' => __( 'Address, City and Zipcode', 'really-simple-ssl' ),
            'type'        => 'textarea',
            'default'     => '',
            'label'       => __( "Address", 'really-simple-ssl' ),
        ),

        'country_company' => array(
            'step'     => 1,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'select',
            'options'  => $this->countries,
            'default'  => '',
            'label'    => __( "Country", 'really-simple-ssl' ),
            'required' => true,
            'tooltip'  => __( "This setting is automatically pre-filled based on your WordPress language setting.", 'really-simple-ssl' ),
        ),

        'contact_company' => array(
            'step'     => 1,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'options'  => array(
                'manually' => __( 'I would like to add the above contact details to the terms & conditions', 'really-simple-ssl' ),
                'webpage'  => __( 'I would like to select an existing page', 'really-simple-ssl' ),
            ),
            'default'  => '',
            'tooltip'  => __( "An existing page would be a contact or an 'about us' page where your contact details are readily available, or a contact form is present.", 'really-simple-ssl' ),
            'label'    => __( "How do you wish visitors to contact you?", 'really-simple-ssl' ),
            'required' => true,
        ),

        'email_company'      => array(
            'step'      => 1,
            'section'   => 1,
            'source'    => 'lets-encrypt',
            'type'      => 'email',
            'default'   => '',
            'tooltip'   => __( "Your email address will be obfuscated on the front-end to prevent spidering.",
                'really-simple-ssl' ),
            'label'     => __( "What is the email address your visitors can use to contact you about the terms & conditions?", 'really-simple-ssl' ),
            'condition' => array(
                'contact_company' => 'manually',
            ),
        ),

        'page_company' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'placeholder' => home_url('/contact/'),
            'type'        => 'url',
            'default'     => '',
            'label'       => __( "Add the URL for your contact details", 'really-simple-ssl' ),

        ),

        // Moet leeg kunnen zijn en handmatig ingevuld. Een upsell naar rsssl en ingevuld als ze rsssl hebben. Wanneer ingevuld -> Tekst toevoegen
        'legal_mention' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'radio',
            'required' => true,
            'label'       => __( "Do you want to refer to your Cookie Policy and Privacy Statement?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,

        ),

        'cookie_policy' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'url',
            'placeholder' => site_url('cookie-policy'),
            'label'       => __( "URL to your Cookie Policy", 'really-simple-ssl' ),
            'condition' => array(
                'legal_mention' => 'yes',
            ),
        ),

        'privacy_policy' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'url',
            'placeholder' => site_url('privacy-statement'),
            'label'       => __( "URL to your Privacy Statement", 'really-simple-ssl' ),
            'condition' => array(
                'legal_mention' => 'yes',
            ),
        ),

        'disclosure_company' => array(
            'step'        => 1,
            'section'     => 1,
            'source'      => 'lets-encrypt',
            'type'        => 'url',
            'placeholder' => site_url('impressum'),
            'help'        => __( "For Germany and Austria, please refer to your Impressum, for other EU countries and the UK you can select a page where your company or personal details are described.",
                    'really-simple-ssl' ) . rsssl_read_more( 'https://rsssl.io/definitions/what-are-statutory-and-regulatory-disclosures/' ),
            'label'       => __( "Where can your visitors find your statutory and regulatory disclosures?", 'really-simple-ssl' ),
        ),

    );

// Questions - Content

$this->fields = $this->fields + array(
        // constante zoeken + callback
        'webshop_content' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Are you running a webshop?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'account_content' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Is there an option to register an account on your website for clients?", 'really-simple-ssl' ),
            'tooltip'  => __( 'This means any registration form or account creation for your customers or website visitors.', 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'delete'            => array(
            'step'      => 2,
            'section'   => 1,
            'source'    => 'lets-encrypt',
            'type'      => 'radio',
            'required'  => true,
            'default'   => '',
            'label'     => __( "Do you want to suspend or delete user accounts of visitors that breach the terms & conditions?", 'really-simple-ssl' ),
            'tooltip'   => __( 'Appends a paragraph to your terms & conditions enabling your to delete any account breaching this document.', 'really-simple-ssl' ),
            'options'   => $this->yes_no,
            'condition' => array(
                'account_content' => 'yes',
            ),
        ),


        // constante zoeken + callback
        'affiliate_content' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you engage in affiliate marketing?", 'really-simple-ssl' ),
            'tooltip'  => __( 'Either by accepting affiliate commission through your webshop or engaging in other affiliate programs.', 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        // constante zoeken + callback
        'forum_content'     => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Is there an option for visitors to post their own content on your websites?", 'really-simple-ssl' ),
            'tooltip'  => __( 'Think about reviews, a forum, comments and other moderated and unmoderated content.', 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'accessibility_content' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you want to include your efforts concerning accessibility?", 'really-simple-ssl' ),
            'help'     => __( 'Extend your document with a reference to your efforts toward accessibility.', 'really-simple-ssl' )
                . rsssl_read_more( 'https://rsssl.io/definitions/what-is-wcag/' ),
            'options'  => $this->yes_no,
        ),

        'age_content' => array(
            'step'     => 2,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Is your website specifically targeted at minors?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'minimum_age' => array(
            'step'      => 2,
            'section'   => 1,
            'source'    => 'lets-encrypt',
            'type'      => 'number',
            'default'   => 12,
            'label'     => __( "What is the minimum appropriate age for your website? ", 'really-simple-ssl' ),
            'tooltip'   => __( 'This will ensure a paragraph explaining a legal guardian must review and agree to these terms & conditions', 'really-simple-ssl' ),
            'condition' => array(
                'age_content' => 'yes',
            ),
        ),

        // Communication
        'electronic_communication' => array(
            'step'     => 2,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you want to state that communication in writing is done electronically?", 'really-simple-ssl' ),
            'tooltip'  => __( 'This will contain a paragraph that communication in writing will be done electronically e.g., email and other digital communication tools.',
                'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'newsletter_communication' => array(
            'step'     => 2,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'tooltip'   => __( 'Order updates, customer service and other direct and specific communication with your clients or users should not be considered.', 'really-simple-ssl' ),
            'label'    => __( "Do you send newsletters?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'majeure_communication' => array(
            'step'     => 2,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you want to enable Force Majeure? ", 'really-simple-ssl' ),
            'help'     => __('Force majeure are occurrences beyond the reasonable control of a party and that will void liability', 'really-simple-ssl' ) . rsssl_read_more( 'https://rsssl.io/what-is-force-majeure/' ),
            'options'  => $this->yes_no,
        ),

        'notice_communication' => array(
            'step'     => 2,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Will you give a written notice of any changes or updates to the terms & conditions before these changes will become effective?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'language_communication'      => array(
            'step'     => 2,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => 'yes',
            'label'    => __( "Do you want to limit the interpretation of this document to your current language?", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),


        // WPML & polylang
        'multilanguage_communication' => array(
            'step'      => 2,
            'section'   => 2,
            'source'    => 'lets-encrypt',
            'type'      => 'multicheckbox',
            'required'  => true,
            'default'   => '',
            'condition' => array(
                'language_communication' => 'no',
            ),
            'label'     => __( "In which languages is this document available for interpretation?", 'really-simple-ssl' ),
            'help'   => __( 'This answer is pre-filled if a multilanguage plugin is available e.g. WPML or Polylang.', 'really-simple-ssl' )
                . rsssl_read_more( 'https://rsssl.io/translating-lets-encrypt/' ),
            'options'   => $this->languages,
        ),

        // Liability
        'sensitive_liability' => array(
            'step'     => 2,
            'section'  => 3,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you offer financial, legal or medical advice?", 'really-simple-ssl' ),
            'tooltip'    => __( "If you answer 'No', a paragraph will explain the content on your website does not constitute professional advice.", 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'max_liability' => array(
            'step'     => 2,
            'section'  => 3,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'required' => true,
            'default'  => '',
            'label'    => __( "Do you want to limit liability with a fixed amount?", 'really-simple-ssl' ),
            'tooltip'  => __( 'If you choose no, liability will be fixed to the amount paid by your customer.', 'really-simple-ssl' ),
            'options'  => $this->yes_no,
        ),

        'about_liability' => array(
            'step'                    => 2,
            'section'                 => 3,
            'source'                  => 'lets-encrypt',
            'placeholder'             => '$1000',
            'type'                    => 'text',
            'default'                 => '',
            'label'                   => __( "Regarding the previous question, fill in the fixed amount including the currency.", 'really-simple-ssl' ),
            'condition'               => array(
                'max_liability' => 'yes',
            ),
        ),

        // Copyright
        'about_copyright' => array(
            'step'     => 2,
            'section'  => 4,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'options'  => array(
                'allrights' => __( 'All rights reserved', 'really-simple-ssl' ),
                'norights'  => __( 'No rights are reserved', 'really-simple-ssl' ),
                'ccattr'    => __( 'Creative commons - Attribution', 'really-simple-ssl' ),
                'ccsal'     => __( 'Creative commons - Share a like', 'really-simple-ssl' ),
                'ccnod'     => __( 'Creative commons - No derivates', 'really-simple-ssl' ),
                'ccnon'     => __( 'Creative commons - Noncommercial', 'really-simple-ssl' ),
                'ccnonsal'  => __( 'Creative commons - Share a like Noncommercial', 'really-simple-ssl' ),
            ),
            'default'  => '',
            'help'   => __( 'Want to know more about Creative Commons?', 'really-simple-ssl' )
                . rsssl_read_more( 'https://rsssl.io/definitions/what-is-creative-commons/' ),
            'label'    => __( "What do you want to do with any intellectual property claims?",
                'really-simple-ssl' ),
            'required' => true,
        ),

        // Returns
        'if_returns' => array(
            'step'    => 2,
            'section' => 5,
            'source'  => 'lets-encrypt',
            'type'    => 'radio',
            'options' => $this->yes_no,
            'default' => 'yes',
            'tooltip' => __( "This will append the conditions for returns and withdrawals, mandatory when selling to consumers in the EU.  ", 'really-simple-ssl' ),
            'label'   => __( "Do you offer returns of goods or the withdrawal of services?", 'really-simple-ssl' ),
        ),

        'refund_period' => array(
            'step'    => 2,
            'section' => 5,
            'minimum' => 14,
            'required' => true,
            'source'  => 'lets-encrypt',
            'type'    => 'number',
            'default' => 14,
            'label'   => __( "What is your refund period in days?", 'really-simple-ssl' ),
            'tooltip'   => __( "EU legislation requires you to offer a minimum of 14 days refund period.", 'really-simple-ssl' ),
            'condition'               => array(
                'if_returns' => 'yes',
            ),
        ),

        'about_returns' => array(
            'step'     => 2,
            'section'  => 5,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'options'  => array(
                'nuts_services'         => __( 'Services and/or digital content.', 'really-simple-ssl' ),
                'nuts_utilities'         => __( 'Utilities - Gas, water and electricity.', 'really-simple-ssl' ),
                'webshop'      => __( 'Products and goods.', 'really-simple-ssl' ),
                'multiples'    => __( 'A contract relating to goods ordered by the consumer and delivered separately.', 'really-simple-ssl' ),
                'subscription' => __( 'Subscription-based delivery of goods.', 'really-simple-ssl' ),
            ),
            'default'  => '',
            'help'     => rsssl_read_more( 'https://rsssl.io/about-return-policies/' ),
            'label'    => __( "Please choose the option that best describes the contract a consumer closes with you through the use of the website.", 'really-simple-ssl' ),
            'condition'               => array(
                'if_returns' => 'yes',
            ),
        ),

        'product_returns' => array(
            'step'      => 2,
            'section'   => 5,
            'source'    => 'lets-encrypt',
            'type'      => 'radio',
            'options'   => $this->yes_no,
            'default'   => '',
            'label'     => __( "Do you want to offer your customer to collect the goods yourself in the event of withdrawal?", 'really-simple-ssl' ),
            'callback_condition' => 'NOT rssslf_nuts',
            'condition'               => array(
                'if_returns' => 'yes',
            ),
        ),

        'costs_returns' => array(
            'step'     => 2,
            'section'  => 5,
            'source'   => 'lets-encrypt',
            'type'     => 'radio',
            'options'  => array(
                'seller'   => __( 'We, the seller', 'really-simple-ssl' ),
                'customer' => __( 'The customer', 'really-simple-ssl' ),
                'maxcost'  => __( 'The goods, by their nature, cannot normally be returned by post and a maximum cost of return applies ', 'really-simple-ssl' ),
            ),
            'default'  => '',
            'label'    => __( "Who will bear the cost of returning the goods?",
                'really-simple-ssl' ),
            'condition'               => array(
                'if_returns' => 'yes',
            ),
        ),

        'max_amount_returned' => array(
            'step'                    => 2,
            'section'                 => 5,
            'source'                  => 'lets-encrypt',
            'type'                    => 'text',
            'default'                 => '',
            'placeholder'             => '$1000',
            'label'                   => __( "Regarding the previous question, fill in the maximum amount including the currency.", 'really-simple-ssl' ),
            'condition'               => array(
                'costs_returns' => 'maxcost',
                'if_returns' => 'yes',
            ),
        ),
    );

// End of Questions
$this->fields = $this->fields + array(
        'create_pages' => array(
            'step'     => 3,
            'section'  => 1,
            'source'   => 'lets-encrypt',
            'callback' => 'terms_conditions_add_pages',
            'label'    => '',
        ),
    );

$this->fields = $this->fields + array(
        'add_pages_to_menu' => array(
            'step'     => 3,
            'section'  => 2,
            'source'   => 'lets-encrypt',
            'callback' => 'terms_conditions_add_pages_to_menu',
            'label'    => '',
        ),
    );

$this->fields = $this->fields + array(
        'finish_setup' => array(
            'step'     => 4,
            'source'   => 'lets-encrypt',
            'callback' => 'last_step',
            'label'    => '',
        ),
    );