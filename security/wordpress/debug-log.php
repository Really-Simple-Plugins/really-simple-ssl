<?php defined( 'ABSPATH' ) or die();

/**
 * Move debug.log to /debug_randomString/ directory
 * @return void
 * @since 6.0
 */
function rsssl_change_debug_log_location() {

    // Run once
    if ( get_site_option('rsssl_debug_log_location_changed') == '1') return;

    // Comment out current debug.log
    rsssl_comment_out_default_debug_log();

    $wpconfig_path = rsssl_find_wp_config_path();
    $wpconfig = file_get_contents($wpconfig_path);
    $regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
    preg_match($regex, $wpconfig, $matches);

    if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {
        // Random folder suffix string
        $debug_log_folder_suffix = strtolower( rsssl_generate_random_string( 10 ) );
        $new_debug_log_folder = trailingslashit( ABSPATH . 'wp-content/debug_' . $debug_log_folder_suffix );
        // Create new debug_randomstring folder
        mkdir($new_debug_log_folder);
        $new_debug_log_name = 'debug.log';
        $new_debug_log_path = trim($new_debug_log_folder) . $new_debug_log_name;

        // Copy over current content if debug.log exists
        if ( file_exists(WP_CONTENT_DIR . '/debug.log' ) ) {
            $old_debug_log = file_get_contents(WP_CONTENT_DIR . '/debug.log' );
        } else {
            $old_debug_log = '';
        }

        file_put_contents($new_debug_log_path, $old_debug_log);

        // Add our new line below the commented out default in wp-config.php
        $new = "\n" . "define( 'WP_DEBUG_LOG', '$new_debug_log_path' );" . "\n";
        $wpconfig = str_replace($matches[0] . ";", $matches[0] . ";" . $new, $wpconfig);

        file_put_contents( $wpconfig_path, $wpconfig );

        // Update options
        update_site_option('rsssl_debug_log_folder_suffix', $debug_log_folder_suffix );
        update_site_option('rsssl_debug_log_location_changed', true);
        delete_site_option('rsssl_debug_log_reverted_by_rsssl');

        // Create a bogus debug.log file in wp-content
        rsssl_add_bogus_debug_log_content();
    }
}

/**
 * Revert to default debug.log location
 * @return void
 * @since 6.0
 */
function rsssl_revert_debug_log_location() {

    // Run once
    if ( get_site_option('rsssl_debug_log_reverted_by_rsssl') == '1') return;

	$wpconfig_path = rsssl_find_wp_config_path();
	$wpconfig = file_get_contents($wpconfig_path);

	// Get current declaration
	$rsssl_debug_log = ABSPATH . 'wp-content/debug_' . get_site_option('rsssl_debug_log_folder_suffix') . '/debug.log';
	if ( ! file_exists( $rsssl_debug_log ) ) return;

    // Move RSSSL debug.log to wp-content/debug.log
    if ( file_exists($rsssl_debug_log ) ) {
        $rsssl_debug_log_contents = file_get_contents($rsssl_debug_log );
    } else {
        $rsssl_debug_log_contents = '';
    }

    file_put_contents( WP_CONTENT_DIR . '/debug.log', $rsssl_debug_log_contents);

    // Regex to detect RSSSL debug.log path
	$rsssl_debug_log_regex = preg_quote($rsssl_debug_log, "/");
	// Check if this declaration exists in wp-config.php
	$regex_rsssl_debug_log = "/(define)(.*$rsssl_debug_log_regex.*)(?=;)/m";
	preg_match($regex_rsssl_debug_log, $wpconfig, $rsssl_declaration_matches);

    // Regex to detect regular commented out debug.log
    $regex_regular_debug_log = "/(\/\/define)(.*WP_DEBUG_LOG.*)(?=;)/m";
    preg_match($regex_regular_debug_log, $wpconfig, $matches_default_debug_log);

    // If wp-config is writable, remove RSSSL debug.log path and uncomment regular debug.log declaration
	if ( ( strlen( $wpconfig ) !=0 ) && is_writable( $wpconfig_path ) ) {
        if ( $rsssl_declaration_matches[0] ) {
            $wpconfig = str_replace($rsssl_declaration_matches[0] . ";", '', $wpconfig);
        }
        
        if ( $matches_default_debug_log[0] ) {
            $wpconfig = str_replace($matches_default_debug_log[0] . "//", '', $wpconfig);
        }

		file_put_contents( $wpconfig_path, $wpconfig );
	}

	// Remove debug_randomstring.log file
	unlink($rsssl_debug_log);
	// Remove debug_randomstring directory
	rmdir(ABSPATH . 'wp-content/debug_' . get_site_option('rsssl_debug_log_folder_suffix'));
	// Delete options
	delete_option('rsssl_debug_log_folder_suffix');
	delete_option('rsssl_debug_log_suffix');
    delete_site_option('rsssl_debug_log_location_changed');
    // Add option to let notices know debug.log has been reverted by RSSSL
    update_site_option('rsssl_debug_log_reverted_by_rsssl', true);
}

/**
 * Populate bogus debug.log in /wp-content
 * @return void
 * @since 6.0
 */
function rsssl_add_bogus_debug_log_content() {
	$debug_log = WP_CONTENT_DIR . '/debug.log';

	// Only change if the file exists
	if ( file_exists( $debug_log ) ) {
        $new_content = 'Access denied';

        file_put_contents($debug_log, $new_content);
	}

}

/**
 * Comment out default debug.log declaration
 * @return void
 * @since 6.0
 */
function rsssl_comment_out_default_debug_log() {

    if ( rsssl_debug_log_in_default_location() ) {

        $wpconfig_path = rsssl_find_wp_config_path();
        $wpconfig = file_get_contents($wpconfig_path);

        // Get WP_DEBUG_LOG declaration
        $regex = "/(define)(.*WP_DEBUG_LOG.*)(?=;)/m";
        preg_match($regex, $wpconfig, $matches);

        if ( strpos($matches[0], '//') !== true ) {
            $wpconfig = str_replace($matches[0], '//' . $matches[0], $wpconfig);
            file_put_contents($wpconfig_path, $wpconfig);
        }
    }
}

/**
 * Function to maybe change the debug.log location
 * @return void
 * @since 6.0
 */
function rsssl_maybe_change_debug_log_location() {

    $options = get_option('rsssl_options');

    // Change debug.log location if option enabled, and location not changed yet
	if ( $options['change_debug_log_location'] == '1' && get_site_option('rsssl_debug_log_location_changed') != '1' )  {
		rsssl_change_debug_log_location();
	} elseif ($options['change_debug_log_location'] != '1' && get_site_option('rsssl_debug_log_location_changed') == '1') {
        rsssl_revert_debug_log_location();
    }

}
add_action('admin_init', 'rsssl_maybe_change_debug_log_location', 999);
