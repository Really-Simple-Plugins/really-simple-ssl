<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

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
add_filter('rsssl_notices', 'user_registration_notice', 50, 2);

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
 * Action to disable user registration
 *
 * @return bool
 */
function rsssl_disable_user_registration($value, $option) {
	return false;
}
add_filter( "option_users_can_register", 'rsssl_disable_user_registration', 999, 2 );