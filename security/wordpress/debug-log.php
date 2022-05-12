<?php defined( 'ABSPATH' ) or die();
error_log("load debug .log");

/**
 * @return void
 * Disable WP_DEBUG_LOG. Revert to default
 */
function rsssl_revert_debug_log_location() {
	error_log("deactivate");

	$wpconfig_path = rsssl_find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get current declaration
	$rsssl_debug_log = ABSPATH . 'wp-content/debug_' . get_site_option('rsssl_debug_log_folder_suffix') . '/debug_' .  get_site_option('rsssl_debug_log_suffix') . '.log';
	if ( ! file_exists( $rsssl_debug_log ) ) return;
	// Replace / with \/ for regex escape
	$rsssl_debug_log_regex = str_replace('/', '\/', $rsssl_debug_log);
	// Check if this declaration exists in wp-config.php
	$regex = "/(define)(.*$rsssl_debug_log_regex.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// Update if wp-config is writable and a regex match has been found
	if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) && $matches[0] ) {
		$wpconfig = str_replace( $matches[0], "define( 'WP_DEBUG_LOG', true)", $wpconfig );
		file_put_contents( $wpconfig_path, $wpconfig );
	}

	// Remove debug_randomstring.log file
	unlink($rsssl_debug_log);
	// Remove debug_randomstring directory
	rmdir(ABSPATH . 'wp-content/debug_' . get_site_option('rsssl_debug_log_folder_suffix'));
	// Delete RSSSL debug.log options
	delete_option('rsssl_debug_log_folder_suffix');
	delete_option('rsssl_debug_log_suffix');
}

/**
 * @return void
 * Change the debug.log name
 *
 */
function rsssl_change_debug_log_name() {
	$debug_log = WP_CONTENT_DIR . '/debug.log';
	// Only change if the file exists
	if ( file_exists( $debug_log ) ) {
		$debug_log_suffix = strtolower( rsssl_generate_random_string(5) );
		rename( $debug_log,  'debug_' . $debug_log_suffix . '.log' );
		wp_delete_file(trailingslashit(WP_CONTENT_DIR ) . 'debug.log');
		update_site_option('rsssl_debug_log_suffix', $debug_log_suffix);
	}
}

/**
 * @return void
 *
 * Change debug.log location
 */
function rsssl_change_debug_log_location() {
	// Change current debug.log name
	rsssl_change_debug_log_name();

    $wpconfig_path = rsssl_find_wp_config_path();
    $wpconfig = file_get_contents($wpconfig_path);
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

    if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) && rsssl_contains_debug_log_declaration() ) {
		// Random folder suffix string
	    $debug_log_folder_suffix = strtolower( rsssl_generate_random_string( 10 ) );
		$new_location = trailingslashit( ABSPATH . 'wp-content/debug_' . $debug_log_folder_suffix );
		// Create new debug_randomstring folder
		mkdir($new_location);
		// Generate new debug_randomstring.log name
		$new_debug_log_name = 'debug_' . get_site_option('rsssl_debug_log_suffix') .  '.log';
		$path = trim($new_location) . trim($new_debug_log_name);
	    $new = "define( 'WP_DEBUG_LOG', '$path' )";
		// Update wp-config.php to new debug_randomstring.log location
	    $wpconfig = str_replace( $matches[0], $new, $wpconfig );
	    file_put_contents( $wpconfig_path, $wpconfig );

		// Update options
	    update_site_option('rsssl_debug_log_folder_suffix', $debug_log_folder_suffix );
    }
}

/**
 * @return bool
 *
 * Check if wp-config.php contains debug.log declaration
 */
function rsssl_contains_debug_log_declaration() {
	$wpconfig_path = rsssl_find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get WP_DEBUG_LOG declaration
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// If str contains true, location is default
	if ( $matches && strpos($matches[0], 'true' ) !== FALSE ) {
		return true;
	}

	return false;
}

/**
 * @return void
 * Handle differently based on option value
 */
function rsssl_maybe_change_debug_log_location() {
	if ( rsssl_debug_log_in_default_location() && rsssl_get_option('change_debug_log_location') ){
		rsssl_change_debug_log_location();
	} else if (!rsssl_debug_log_in_default_location() && !rsssl_get_option('change_debug_log_location')) {
		rsssl_revert_debug_log_location();
	}
}
add_action('init', 'rsssl_maybe_change_debug_log_location', 999);