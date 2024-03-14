<?php
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$rsssl_settings = get_option( 'rsssl_options' );
if ( isset( $rsssl_settings['delete_data_on_uninstall'] ) && $rsssl_settings['delete_data_on_uninstall'] ) {
	$rsssl_options = [
		"rsssl_changed_files",
		"rsssl_enable_csp_defaults",
		"rsssl_elementor_upgraded",
		"rsssl_redirect_to_http_check",
		"rsssl_pro_permissions_policy_headers_for_php",
		"rsssl_license_attempts",
		"rsssl_csp_report_url",
		"rsssl_iteration",
		"rsssl_scan",
		"rsssl_progress",
		"rsssl_current_action",
		"rsssl_scan_type",
		"rsssl_scan_active",
		"rsssl_xmlrpc_db_version",
		"rsssl_first_version",
		"rsssl-pro-current-version",
		"rsssl_xmlrpc_learning_mode_activation_time",
		"rsssl_pro_defaults_set",
		"rsssl_after_default_setup_completed",
		"rsssl_ms_elementor_private_replace_progress",
		"rsssl_ms_elementor_urls_upgraded",
		"rsssl_ms_elementor_public_replace_progress",
		"rsssl_csp_report_only_activation_time",
		"rsssl_csp_report_token",
		"rsssl_key",
		"rsssl_transients",
		"rsssl_pro_license_activation_limit",
		"rsssl_ssl_verify",
		"rsssl_pro_license_activations_left",
		"rsssl_pro_license_expires",
		"rsssl_csp_db_version",
		"rsssl_debug_log_folder_suffix",
		"rsssl_xmlrpc_learning_mode_activation_time",
		"rsssl_port_check_2082",
		"rsssl_port_check_8443",
		"rsssl_port_check_2222",
		"rsssl_csp_db_upgraded",
		"rsssl_scan_completed_no_errors",
		"rsssl_last_scan_time",
		"rsssl_test_wp_login_available",
		"rsssl_country_db_version",
		"rsssl_country_import_version",
		"rsssl_geo_ip_database_file",
		"rsssl_login_attempts_db_version",
		'rsssl_notification_email',
		'rsssl_remaining_tasks',
		'rsssl_activation_timestamp',
		'rsssl_activation_timestamp',
		'rsssl_flush_caches',
		'rsssl_skip_dns_check',
		'rsssl_skip_challenge_directory_request',
		'rsssl_hosting_dashboard',
		'rsssl_options',
		'rsssl_le_dns_tokens',
		'rsssl_le_dns_records_verified',
		'rsssl_private_key_path',
		'rsssl_certificate_path',
		'rsssl_intermediate_path',
		'rsssl_le_certificate_generated_by_rsssl',
		'rsssl_ssl_dirname',
		'rsssl_create_folders_in_root',
		'rsssl_htaccess_file_set_',
		'rsssl_initial_alias_domain_value_set',
		'rsssl_le_start_renewal',
		'rsssl_le_start_installation',
		'rsssl_le_installation_progress',
		'rsssl_activation_time',
		'rsssl_le_certificate_installed_by_rsssl',
		'rsssl_installation_error',
		'rsssl_le_dns_configured_by_rsssl',
		'rsssl_onboarding_dismissed',
		'rsssl_ssl_detection_overridden',
		'rsssl_http_methods_allowed',
		'rsssl_show_onboarding',
		'rsssl_deactivate_list',
		'rsssl_firewall_error',
		'rsssl_completed_fixes',
		'rsssl_rest_api_optimizer_not_writable',
		'rsssl_ssl_labs_data',
		'rsssl_current_version',
		'rsssl_network_activation_status',
		'rsssl_run',
		'rsssl_wp_version_detected',
		'rsssl_admin_notices',
		'rsssl_plusone_count',
		'rsssl_siteprocessing_progress',
		'rsssl_ssl_activation_active',
		'rsssl_network_activation_status',
		'rsssl_siteprocessing_progress',
		'rsssl_header_detection_nonce',
		'rsssl_htaccess_error',
		'rsssl_htaccess_rules',
		'rsssl_options',
		'rsssl_key',
	];
	foreach ( $rsssl_options as $rsssl_option_name ) {
		delete_option( $rsssl_option_name );
		delete_site_option( $rsssl_option_name );
	}
	$rsssl_transients = [
		'rsssl_tls_version',
		'rsssl_redirects_to_homepage',
		'rsssl_cert_expiration_date',
		'rsssl_sent_cert_expiration_warning',
		'rsssl_scan_post_count',
		'rsssl_scan',
		'rsssl_pro_redirect_to_settings_page',
		'rsssl_stop_certificate_expiration_check',
		'rsssl_pro_license_status',
		'rsssl_xmlrpc_allowed',
		'rsssl_http_methods_allowed',
		'rsssl_code_execution_allowed_status',
		'rsssl_directory_indexing_status',
		'rsssl_htaccess_test_success',
		'rsssl_can_use_curl_headers_check',
		'rsssl_curl_error',
		'rsssl_mixed_content_fixer_detected',
		'rsssl_admin_notices',
		'rsssl_plusone_count',
		'rsssl_testpage',
		'rsssl_plugin_download_active',
		'rsssl_le_generate_attempt_count',
		'rsssl_alias_domain_available',
		'rsssl_le_install_attempt_count',
		'rsssl_cw_t',
		'rsssl_cw_server_id',
		'rsssl_redirect_to_settings_page',
		'rsssl_certinfo',
	];
	foreach ( $rsssl_transients as $rsssl_transient ) {
		delete_transient( $rsssl_transient );
		delete_site_transient( $rsssl_transient );
	}

	require_once(ABSPATH . 'wp-admin/includes/file.php');
	WP_Filesystem();

	function rsssl_delete_directory_wpfilesystem($dir) {
		global $wp_filesystem;
		if ($wp_filesystem->is_dir($dir)) {
			$objects = $wp_filesystem->dirlist($dir);
			foreach ($objects as $object => $objectdata) {
				if ($wp_filesystem->is_dir($dir . "/" . $object)) {
					rsssl_delete_directory_wpfilesystem($dir . "/" . $object);
				}
				else {
					$wp_filesystem->delete($dir . "/" . $object);
				}
			}
			$wp_filesystem->rmdir($dir);
		}
	}

	$upload_dir = wp_upload_dir();
	$really_simple_ssl_dir = $upload_dir['basedir'] . '/really-simple-ssl';
	rsssl_delete_directory_wpfilesystem($really_simple_ssl_dir);

	global $wpdb;
	$table_names = array(
		$wpdb->base_prefix . 'rsssl_csp_log',
		$wpdb->base_prefix . 'rsssl_xmlrpc',
		$wpdb->base_prefix . 'rsssl_country',
		$wpdb->base_prefix . 'rsssl_login_attempts',
	);

	foreach($table_names as $table_name){
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
	}
}
