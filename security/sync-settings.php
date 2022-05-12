<?php
add_filter('rsssl_notices', 'rsssl_show_notices_for_mismatches', 50, 1);
add_action( 'admin_init', 'rsssl_sync_wordpress_settings');

/**
 * @return void
 *
 * Check for mismatches between RSSSL & WordPress options
 * If so, update RSSSL option and add a notice
 */
function rsssl_sync_wordpress_settings() {

    if ( ! get_transient('rsssl_settings_mismatch_check' ) ) {
        delete_option('rsssl_option_mismatches');
        $mismatches = array();

        if ( DEFINED('WP_DEBUG') && !rsssl_get_option('change_debug_log_location') ) {
            rsssl_update_option('change_debug_log_location', true);
            $mismatches[] = 'rsssl_debug_log_modified';
        } elseif ( ! DEFINED('WP_DEBUG') && rsssl_get_option('change_debug_log_location') ) {
            rsssl_update_option('change_debug_log_location', false);
            $mismatches[] = 'rsssl_debug_log_modified';
        }

        if ( DEFINED('DISALLOW_FILE_EDIT') && !rsssl_get_option('disable_file_editing') ) {
            rsssl_update_option('disable_file_editing', true);
            $mismatches[] = 'rsssl_file_editing';
        } elseif ( ! DEFINED('DISALLOW_FILE_EDIT') && rsssl_get_option('disable_file_editing') ) {
            //rsssl_update_option('disable_file_editing', false);
            $mismatches[] = 'rsssl_file_editing';
        }

        update_option('rsssl_option_mismatches', $mismatches );
        set_transient('rsssl_settings_mismatch_check', true, MINUTE_IN_SECONDS * 5);
    }

}

/**
 * @return void
 *
 * Show notices for mismatched RSSSL & WordPress options
 */
function rsssl_show_notices_for_mismatches($notices) {

    $mismatches = get_option('rsssl_option_mismatches');

    if ( isset( $mismatches['rsssl_debug_log_modified'] ) ) {
        $notices['rsssl-debug-log-modified-mismatch'] = array(
            'callback' => '_true_',
            'score' => 5,
            'output' => array(
                'allowed' => array(
                    'msg' => __("Debugging value has been changed but not by Really Simple SSL.", "really-simple-ssl"),
                    'icon' => 'open',
                    'dismissible' => true,
                ),
            ),
        );
    }

    if ( isset( $mismatches['rsssl_file_editing'] ) ) {
        $notices['rsssl-file-editing-mismatch'] = array(
            'callback' => '_true_',
            'score' => 5,
            'output' => array(
                'allowed' => array(
                    'msg' => __("File editing has been changed but not by Really Simple SSL.", "really-simple-ssl"),
                    'icon' => 'open',
                    'dismissible' => true,
                ),
            ),
        );
    }
	return $notices;
}

/**
 * Enable this option in RSSSL if the WP option is disabled.
 * @param $value
 * @param $option
 *
 * @return bool|mixed
 */
function rsssl_option_anyone_can_register( $field, $field_id ) {
	if ( $field_id === 'disable_anyone_can_register' && !$field['value'] && !get_option('users_can_register') ) {
		$field['disabled'] = true;
		$field['value'] = true;
	}
	return $field;
}
add_filter("rsssl_field", 'rsssl_option_anyone_can_register', 10,2);

/**
 * When disable debug log location is disabled, revert back
 */
if ( get_site_option('rsssl_debug_log_suffix') && !rsssl_get_option('change_debug_log_location') && !rsssl_debug_log_in_default_location() && rsssl_is_debug_log_enabled() ) {
	$file = rsssl_path . 'security/wordpress/debug-log.php';
	require_once($file);
}
