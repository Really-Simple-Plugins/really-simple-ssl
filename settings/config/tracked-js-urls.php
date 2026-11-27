<?php

defined('ABSPATH') or die();

/**
 * Website links for the settings JavaScript, keyed by the name the components
 * use in getTrackedUrl(). PHP builds them, because JavaScript cannot work out
 * the edition or partner attribution.
 *
 * The key is also the utm_content value, so never rename a shipped key.
 *
 * @return array<string, string>
 */
return [
    'dashboard_other_plugins_about' => rsssl_tracked_url('about-us/', 'documentation', 'dashboard_other_plugins_about'),
    'dashboard_progress_upgrade' => rsssl_tracked_url('pro', 'upgrade', 'dashboard_progress_upgrade'),
    'dashboard_ssl_labs_read_more' => rsssl_tracked_url('instructions/about-ssl-labs/', 'documentation', 'dashboard_ssl_labs_read_more'),
    'dashboard_wordpress_security_read_more' => rsssl_tracked_url('why-wordpress-is-insecure/', 'documentation', 'dashboard_wordpress_security_read_more'),
    'dashboard_vulnerabilities_read_more' => rsssl_tracked_url('staying-ahead-of-vulnerabilities/', 'documentation', 'dashboard_vulnerabilities_read_more'),
    'dashboard_hardening_read_more' => rsssl_tracked_url('hardening-your-websites-security/', 'documentation', 'dashboard_hardening_read_more'),
    'dashboard_login_protection_read_more' => rsssl_tracked_url('login-protection-as-essential-security/', 'documentation', 'dashboard_login_protection_read_more'),
    'dashboard_security_headers_read_more' => rsssl_tracked_url('protecting-site-visitors-with-security-headers', 'documentation', 'dashboard_security_headers_read_more'),
    'dashboard_firewall_read_more' => rsssl_tracked_url('enable-an-efficient-and-performant-firewall/', 'documentation', 'dashboard_firewall_read_more'),
    'dashboard_tips_documentation' => rsssl_tracked_url('knowledge-base-overview/', 'documentation', 'dashboard_tips_documentation'),
    'dashboard_header_documentation' => rsssl_tracked_url('knowledge-base', 'documentation', 'dashboard_header_documentation'),
    'dashboard_header_upgrade' => rsssl_tracked_url('pro', 'upgrade', 'dashboard_header_upgrade'),
    'lets_encrypt_directories_dns_help' => rsssl_tracked_url('lets-encrypt-authorization-with-dns', 'documentation', 'lets_encrypt_directories_dns_help'),
    'lets_encrypt_dns_txt_help' => rsssl_tracked_url('how-to-add-a-txt-record-to-dns', 'documentation', 'lets_encrypt_dns_txt_help'),
    'onboarding_finish_upgrade' => rsssl_tracked_url('pro', 'upgrade', 'onboarding_finish_upgrade'),
    'onboarding_email_privacy' => rsssl_tracked_url('legal/privacy-statement/', 'onboarding', 'onboarding_email_privacy'),
    'settings_support_submit' => rsssl_tracked_url('support', 'support', 'settings_support_submit'),
    'settings_load_error_read_more' => rsssl_tracked_url('instructions/how-to-debug-a-blank-settings-page-in-really-simple-ssl/', 'documentation', 'settings_load_error_read_more'),
];
