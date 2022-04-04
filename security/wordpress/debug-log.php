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
 * @return false|string
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
    if ( strpos($matches[0], 'true' ) !== FALSE ) {
		error_log("Default found!");
        return true;
    }

	return false;
}

/**
 * @return void
 * Disable WP_DEBUG_LOG
 */
function rsssl_disable_debug_log() {
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
function rsssl_enable_debug_log() {
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

	if ( get_option('rsssl_debug_log_location_changed') ) return;

    $wpconfig_path = RSSSL()->really_simple_ssl->find_wp_config_path();
    $wpconfig = file_get_contents($wpconfig_path);

	$regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
	preg_match($regex, $wpconfig, $matches);

    if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) && rsssl_contains_debug_log_declaration() ) {

	    $random_string = rsssl_generate_random_string( 10 );
		$new_location = ABSPATH . '/wp-content/debug_' . $random_string;
		mkdir($new_location);
	    $new = "define( 'WP_DEBUG_LOG', '$new_location' . '/debug.log' )";
	    $wpconfig = str_replace( $matches, $new, $wpconfig );
	    file_put_contents( $wpconfig_path, $wpconfig );


		update_option('rsssl_debug_log_location_changed', true);
	}
}

/**
 * Check if debug.log is saved to default location
 */
function rsssl_is_default_debug_log_location() {
    if (
//        rsssl_contains_debug_log_declaration() &&
        ! get_option('rsssl_debug_log_location_changed') )
    {
        return true;
    }

    return false;
}

add_action('admin_init', 'rsssl_change_debug_log_location');

