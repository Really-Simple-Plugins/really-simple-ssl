<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

function hide_debug_log_notice( $notices ) {
    $notices['debug-log-notice'] = array(
        'callback' => 'contains_debug_log_declaration',
        'score' => 5,
        'output' => array(
            'default' => array(
                'msg' => __("Errors are logged to default debug.log location.", "really-simple-ssl"),
                'icon' => 'open',
                'dismissible' => true,
            ),
        ),
    );

    return $notices;
}

/**
 * @return false|string
 *
 * Check if wp-config.php contains debug.log declaration
 */
function contains_debug_log_declaration() {
    $wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
    $wpconfig = file_get_contents($wpconfig_path);

	// Get WP_DEBUG_LOG declaration
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// If str contains true, location is default
    if ( strpos($matches[0], 'true' ) !== FALSE ) {
		error_log("Default found!");
        return 'default';
    }

	return false;
}

/**
 * @return void
 * Disable WP_DEBUG_LOG
 */
function disable_debug_log() {
	$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get WP_DEBUG_LOG declaration
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// If str contains true, location is default
	if ( strpos($matches[0], 'true' ) !== FALSE ) {
		if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {
			$new      = str_replace( 'true', 'false', $matches[0] );
			$wpconfig = str_replace( $matches[0], $new, $wpconfig );
			file_put_contents( $wpconfig_path, $wpconfig );
		}
	}
}

/**
 * @return void
 *
 * Enable debugging in WordPress
 */
function enable_debug_log() {
	$wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get WP_DEBUG_LOG declaration
	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

	// If str contains true, location is default
	if ( strpos($matches[0], 'false' ) !== FALSE ) {
		if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {
			$new      = str_replace( 'false', 'true', $matches[0] );
			$wpconfig = str_replace( $matches[0], $new, $wpconfig );
			file_put_contents( $wpconfig_path, $wpconfig );
		}
	}
}

/**
 * @return void
 *
 * Change debug.log location
 */
function rsssl_change_debug_log_location() {

    $wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
    $wpconfig = file_get_contents($wpconfig_path);

	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

    if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {

	    $new = "define( 'WP_DEBUG_LOG', 'wp-content/uploads/debug.log' )";
	    $wpconfig = str_replace( $matches[0], $new, $wpconfig );
	    file_put_contents( $wpconfig_path, $wpconfig );
	}
}

