<?php

if (!defined('ABSPATH')) {
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
        'metricool' => [
            'slug' => 'metricool',
            'activation_slug' => 'metricool/metricool.php',
            'title' => 'Metricool - ' . (did_action('init') ? esc_html__('Social Media Management', 'really-simple-ssl') : 'Social Media Management'),
            "pre_checked" => false,
        ],
        'simplybook' => [
            'slug' => 'simplybook',
            'activation_slug' => 'simplybook/simplybook.php',
            'upgrade_url' => 'https://simplybook.me/en/pricing',
            'title' => 'SimplyBook.me - ' . (did_action('init') ? esc_html__('Online Booking System', 'really-simple-ssl') : 'Online Booking System'),
            "pre_checked" => false,
        ],
        'complianz-gdpr' => [
            'slug' => 'complianz-gdpr',
            'activation_slug' => 'complianz-gdpr/complianz-gpdr.php',
            'constant_premium' => 'cmplz_premium',
            'upgrade_url' => 'https://complianz.io?src=rsssl-plugin',
            'title' => 'Complianz - ' . (did_action('init') ? esc_html__('Consent Management as it should be', 'really-simple-ssl') : 'Consent Management as it should be'),
            "pre_checked" => true,
        ],
        'complianz-terms-conditions' => [
            'slug' => 'complianz-terms-conditions',
            'activation_slug' => 'complianz-terms-conditions/complianz-terms-conditions.php',
            'upgrade_url' => 'https://complianz.io?src=rsssl-plugin',
            'title' => 'Complianz - ' . (did_action('init') ? esc_html__('Terms & Conditions', 'really-simple-ssl') : 'Terms & Conditions'),
            "pre_checked" => true,
        ],
    ],
];
