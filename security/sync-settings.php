<?php
if ( is_admin() ) {
    add_filter('rsssl_notices', 'rsssl_show_notices_for_mismatches', 50, 3);
}

add_action( 'admin_init', 'rsssl_sync_wordpress_settings');
add_action( 'rsssl_after_saved_fields', 'maybe_update_wordpress_user_registration_option' );
add_action( 'update_option_users_can_register', 'maybe_update_rsssl_user_registration_option' );

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

//        if ( get_option('users_can_register') !== rsssl_get_option('rsssl_disable_anyone_can_register') ) {
//            rsssl_update_option('rsssl_disable_anyone_can_register', get_option('users_can_register'));
//            $mismatches[] = 'rsssl_disable_anyone_can_register';
//        }

        if ( DEFINED('WP_DEBUG') && rsssl_get_option('rsssl_change_debug_log_location') != '1') {
            rsssl_update_option('rsssl_change_debug_log_location', true);
            $mismatches[] = 'rsssl_debug_log_modified';
        } elseif ( ! DEFINED('WP_DEBUG') && rsssl_get_option('rsssl_change_debug_log_location' == '1') ) {
            rsssl_update_option('rsssl_change_debug_log_location', false);
            $mismatches[] = 'rsssl_debug_log_modified';
        }

        if ( DEFINED('DISALLOW_FILE_EDIT') && rsssl_get_option('rsssl_disable_file_editing') != '1') {
            rsssl_update_option('rsssl_disable_file_editing', true);
            $mismatches[] = 'rsssl_file_editing';
        } elseif ( ! DEFINED('DISALLOW_FILE_EDIT') && rsssl_get_option('rsssl_disable_file_editing') == '1') {
            rsssl_update_option('rsssl_disable_file_editing', false);
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
function rsssl_show_notices_for_mismatches() {

    $mismatches = get_option('rsssl_option_mismatches');

    if ( isset( $mismatches['rsssl_disable_anyone_can_register'] ) ) {
        $notices['rsssl-anyone-can-register-mismatch'] = array(
            'callback' => '_true_',
            'score' => 5,
            'output' => array(
                'allowed' => array(
                    'msg' => __("The anyone can register option does not match the value in Really Simple SSL.", "really-simple-ssl"),
                    'icon' => 'open',
                    'dismissible' => true,
                ),
            ),
        );
    }

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
}

/**
 * @return void
 *
 * Sync users can register option when updated via Really Simple SSL
 */
function maybe_update_wordpress_user_registration_option() {
    if ( get_option('users_can_register') != rsssl_get_option('rsssl_disable_anyone_can_register') ) {
        update_option('users_can_register', rsssl_get_option('rsssl_disable_anyone_can_register' ) );
    }
}

/**
 * @return void
 *
 * Sync user can register option when updated via the WP general settings option
 */
function maybe_update_rsssl_user_registration_option()
{
    if ( get_option('users_can_register') ) {
        rsssl_update_option('rsssl_disable_anyone_can_register', false);
    } else {
        rsssl_update_option('rsssl_disable_anyone_can_register', true);
    }
}