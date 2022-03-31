<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return void
 *
 * Rename admin user (user ID 1)
 */
function rename_admin_user() {

    if ( rsssl_get_option('rsssl_rename_admin_user') === '1' && ! get_option('rsssl_admin_user_updated') ) {

        // Get user data for UID 1
        $userdata = get_userdata(1);
        $login = $userdata->user_login;

        if ( current_user_can('manage_options' ) && $login === 'admin' ) {
            // Replace admin user with new admin user
            wp_create_user( rsssl_generate_random_string(12), rsssl_generate_random_string(24), $userdata->user_email );

            // Attribute posts to new user

            // Delete old user

            update_option('rsssl_admin_user_updated', true);
        }
    }
}

function rsssl_has_admin_user() {
    return true;
}
