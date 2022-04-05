<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

function hide_debug_log_notice( $notices ) {
    $notices['debug-log-notice'] = array(
        'callback' => 'contains_debug_log_declaration',
        'score' => 5,
        'output' => array(
            '_true_' => array(
                'msg' => __("Errors are logged to default debug.log location.", "really-simple-ssl"),
                'icon' => 'open',
                'dismissible' => true,
            ),
        ),
    );

    return $notices;
}

/**
 * @return bool
 *
 * Check if wp-config.php contains debug.log declaration
 */
function rsssl_contains_debug_log_declaration() {
    $wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
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
 * Disable WP_DEBUG_LOG. Revert to default
 */
function rsssl_revert_debug_log() {

	$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get current declaration
	$rsssl_debug_log = ABSPATH . 'wp-content/debug_' . get_option('rsssl_debug_log_folder_suffix') . '/debug_' .  get_option('rsssl_debug_log_suffix') . '.log';
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
	rmdir(ABSPATH . 'wp-content/debug_' . get_option('rsssl_debug_log_folder_suffix'));
	// Delete RSSSL debug.log options
	delete_option('rsssl_debug_log_location_changed');
	delete_option('rsssl_debug_log_folder_suffix');
	delete_option('rsssl_debug_log_suffix');
}

/**
 * @return void
 *
 * Enable debugging in WordPress
 */
function rsssl_enable_debug_log() {
	$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get WP_DEBUG_LOG declaration
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// If str contains true, location is default
	if ( strpos( $matches[0], 'false' ) !== FALSE ) {
		if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {
			$new      = str_replace( 'false', 'true', $matches[0] );
			$wpconfig = str_replace( $matches[0], $new, $wpconfig );
			file_put_contents( $wpconfig_path, $wpconfig );
		}
	}
}

/**
 * @return void
 * Change the debug.log name
 *
 */
function rsssl_change_debug_log_name() {
	$debug_log = ABSPATH . 'wp-content/debug.log';
	// Only change if the file exists
	if ( file_exists( $debug_log ) ) {
		$debug_log_suffix = strtolower( rsssl_generate_random_string(5) );

		rename( $debug_log,  'debug_' . $debug_log_suffix . '.log' );

		wp_delete_file(trailingslashit(WP_CONTENT_DIR ) . 'debug.log');

		update_option('rsssl_debug_log_suffix', $debug_log_suffix);
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

	// Do not change if location has already been changed
	if ( get_option('rsssl_debug_log_location_changed') ) return;

    $wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
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
		$new_debug_log_name = 'debug_' . get_option('rsssl_debug_log_suffix') .  '.log';
		$path = trim($new_location) . trim($new_debug_log_name);
	    $new = "define( 'WP_DEBUG_LOG', '$path' )";
		// Update wp-config.php to new debug_randomstring.log location
	    $wpconfig = str_replace( $matches[0], $new, $wpconfig );
	    file_put_contents( $wpconfig_path, $wpconfig );

		// Update options
		update_option('rsssl_debug_log_location_changed', true);
	    update_option('rsssl_debug_log_folder_suffix', $debug_log_folder_suffix );
    }
}

/**
 *
 * @return bool
 * Check if debug.log is saved to default location
 */
function rsssl_is_default_debug_log_location() {
    if ( ! get_option('rsssl_debug_log_location_changed') ) {
        return true;
    }

    return false;
}

/**
 * @return bool
 *
 * Check if debugging in WordPress is enabled
 */
function rsssl_is_debug_log_enabled() {
	if ( defined('WP_DEBUG') && defined('WP_DEBUG_LOG') ) {
		return true;
	}

	return false;
}

add_action('admin_init', 'rsssl_change_debug_log_name');