<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

$settings = get_option('rsssl_options');
if (isset($settings['delete_data_on_uninstall']) && $settings['delete_data_on_uninstall']) {
	$options = [
		"rsssl_remaining_tasks",
		"rsssl_activation_timestamp",
		"rsssl_activation_timestamp",
		"rsssl_flush_caches",
		"rsssl_skip_dns_check",
		"rsssl_skip_challenge_directory_request",
		"rsssl_hosting_dashboard",
		"rsssl_options",
		"rsssl_le_dns_tokens",
		"rsssl_le_dns_records_verified",
		"rsssl_private_key_path",
		"rsssl_certificate_path",
		"rsssl_intermediate_path",
		"rsssl_le_certificate_generated_by_rsssl",
		"rsssl_ssl_dirname",
		"rsssl_create_folders_in_root",
		"rsssl_htaccess_file_set_",
		"rsssl_initial_alias_domain_value_set",
		"rsssl_le_start_renewal",
		"rsssl_le_start_installation",
		"rsssl_le_installation_progress",
		"rsssl_activation_time",
		"rsssl_le_certificate_installed_by_rsssl",
		"rsssl_installation_error",
		"rsssl_le_dns_configured_by_rsssl",
		"rsssl_onboarding_dismissed",
		"rsssl_ssl_detection_overridden",
		"rsssl_show_onboarding",
		"rsssl_deactivate_list",
		"rsssl_firewall_error",
		"rsssl_completed_fixes",
		"rsssl_rest_api_optimizer_not_writable",
		"rsssl_ssl_labs_data",
		"rsssl_current_version",
		"rsssl_network_activation_status",
		"rsssl_run",
		"rsssl_siteprocessing_progress",
		"rsssl_ssl_activation_active",
		"rsssl_network_activation_status",
		"rsssl_siteprocessing_progress",
		"rsssl_header_detection_nonce",
		"rsssl_htaccess_error",
		"rsssl_htaccess_rules",
		"rsssl_options",
		"rsssl_key",
		"rsssl_6_upgrade_completed",
	];
	foreach ( $options as $option_name ) {
		delete_option( $option_name );
		delete_site_option( $option_name );
	}
	$transients = [
		"rsssl_xmlrpc_allowed",
		"rsssl_http_methods_allowed",
		"rsssl_code_execution_allowed_status",
		"rsssl_directory_indexing_status",
		"rsssl_wp_version_detected",
		"rsssl_htaccess_test_success",
		"rsssl_can_use_curl_headers_check",
		"rsssl_curl_error",
		"rsssl_mixed_content_fixer_detected",
		"rsssl_admin_notices",
		"rsssl_plusone_count",
		"rsssl_testpage",
		"rsssl_plugin_download_active",
		"rsssl_le_generate_attempt_count",
		"rsssl_alias_domain_available",
		"rsssl_le_install_attempt_count",
		"rsssl_cw_t",
		"rsssl_cw_server_id",
		"rsssl_redirect_to_settings_page",
		"rsssl_certinfo",
	];
	foreach($transients as $transient) {
		delete_transient( $transient );
		delete_site_transient( $transient );
	}
}