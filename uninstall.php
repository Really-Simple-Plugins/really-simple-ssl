<?php
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( defined('RSSSL_UPGRADING_TO_PRO') ) {
	exit();
}

$rsssl_settings = is_multisite() ? get_site_option( 'rsssl_options' ) : get_option( 'rsssl_options' );
if ( isset( $rsssl_settings['delete_data_on_uninstall'] ) && $rsssl_settings['delete_data_on_uninstall'] ) {
	// Clean up plugin data.
	global $wpdb;

	/*
	 * Option and transient records live in wp_options with predictable prefixes.
	 * Using SQL LIKE patterns here keeps uninstall resilient against older keys
	 * that may no longer be listed explicitly in the codebase.
	 */
	$option_patterns = [
		'rsssl_%',
		'rlrsssl_%',
		'_transient_rsssl_%',
		'_transient_timeout_rsssl_%',
		'_transient_rlrsssl_%',
		'_transient_timeout_rlrsssl_%',
		'_site_transient_rsssl_%',
		'_site_transient_timeout_rsssl_%',
		'_site_transient_rlrsssl_%',
		'_site_transient_timeout_rlrsssl_%',
	];

	foreach ( $option_patterns as $pattern ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
	}

	if ( is_multisite() ) {
		/*
		 * Network-level settings and site transients are stored in sitemeta on
		 * multisite installs, so they need the same prefix-based cleanup there.
		 */
		$site_meta_patterns = [
			'rsssl_%',
			'rlrsssl_%',
			'_site_transient_rsssl_%',
			'_site_transient_timeout_rsssl_%',
			'_site_transient_rlrsssl_%',
			'_site_transient_timeout_rlrsssl_%',
		];

		foreach ( $site_meta_patterns as $pattern ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
					$pattern
				)
			);
		}
	}

	/*
	 * User meta keys are not always stored as strict prefixes. A contains-match
	 * keeps the uninstall cleanup compatible with keys that embed rsssl names.
	 */
	$usermeta_patterns = [
		'%rsssl_%',
		'%rlrsssl_%',
	];

	foreach ( $usermeta_patterns as $pattern ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
				$pattern
			)
		);
	}


	require_once(ABSPATH . 'wp-admin/includes/file.php');
	WP_Filesystem();

	/**
	 * Recursively remove the plugin upload directory via WP_Filesystem.
	 *
	 * This keeps uninstall cleanup compatible with the filesystem abstraction
	 * WordPress has initialized for the current environment.
	 *
	 * @param string $dir Absolute path to the directory that should be removed.
	 */
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
		$wpdb->base_prefix . 'rsssl_geo_block',
        $wpdb->base_prefix . 'rsssl_event_logs',
	);

	foreach($table_names as $table_name){
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
	}
}
