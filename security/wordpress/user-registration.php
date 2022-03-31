<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'user_registration_notice', 50, 2);
}

function user_registration_notice( $notices ) {
    $notices['registration'] = array(
        'callback' => 'rsssl_user_registration_notice',
        'score' => 5,
        'output' => array(
            'can-register' => array(
                'msg' => __("Anyone can register on your site. Consider disabling the 'Anyone can register' option in the Wordpress general settings.", "really-simple-ssl"),
                'icon' => 'warning',
                'plusone' => true,
            ),
        ),
    );

    return $notices;
}

/**
 * @return bool|void
 *
 * Check if user registration is allowed
 */
function rsssl_user_registration_allowed() {
    if ( get_option( 'users_can_register' ) !== false ) {
        return true;
    }

    return false;
}

/**
 * @return string
 * Add a notice for this integration
 */
function rsssl_user_registration_notice()
{
    if ( get_option( 'users_can_register' ) ) {
        return 'can-register';
    }

    return false;
}

/**
 * @return void
 * Disable or enable user registration
 */
function rsssl_maybe_disable_user_registration() {
	update_option('users_can_register', false );
}