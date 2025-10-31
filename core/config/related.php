<?php if (!defined('ABSPATH')) {
    exit;
}

/**
 * The related config can only be used AFTER or ON the 'init' hook.
 *
 * Config documentation:
 * pre_checked: The plugin will be pre checked for installation in the onboarding
 */
return [
    'plugins' => [
        'complianz-gdpr' => [
            'slug' => 'complianz-gdpr',
            'options_prefix' => 'cmplz',
            'activation_slug' => 'complianz-gdpr/complianz-gpdr.php',
            'constant_free' => 'cmplz_version',
            'constant_premium' => 'cmplz_premium',
            'create' => admin_url('admin.php?page=complianz'),
            'wordpress_url' => 'https://wordpress.org/plugins/complianz-gdpr/',
            'upgrade_url' => 'https://complianz.io?src=rsssl-plugin',
            'title' => 'Complianz - ' . (did_action('init') ? esc_html__('Consent Management as it should be', 'really-simple-ssl') : 'Consent Management as it should be'),
            'color' => '#009fff',
            "pre_checked" => true,
        ],
        'complianz-terms-conditions' => [
            'slug' => 'complianz-terms-conditions',
            'options_prefix' => 'cmplz_tc',
            'activation_slug' => 'complianz-terms-conditions/complianz-terms-conditions.php',
            'constant_free' => 'cmplz_tc_version',
            'create' => admin_url('admin.php?page=terms-conditions'),
            'wordpress_url' => 'https://wordpress.org/plugins/complianz-terms-conditions/',
            'upgrade_url' => 'https://complianz.io?src=rsssl-plugin',
            'title' => 'Complianz - ' . (did_action('init') ? esc_html__('Terms & Conditions', 'really-simple-ssl') : 'Terms & Conditions'),
            'color' => '#000000',
            "pre_checked" => true,
        ],
        'simplybook' => [
            'slug' => 'simplybook',
            'options_prefix' => 'simplybook',
            'activation_slug' => 'simplybook/simplybook.php',
            'create' => admin_url('admin.php?page=simplybook-integration'),
            'wordpress_url' => 'https://wordpress.org/plugins/simplybook/',
            'upgrade_url' => 'https://simplybook.me/en/pricing',
            'title' => 'SimplyBook.me - ' . (did_action('init') ? esc_html__('Online Booking System', 'really-simple-ssl') : 'Online Booking System'),
            'color' => '#06ADEF',
            "pre_checked" => false,
        ],
    ],
];