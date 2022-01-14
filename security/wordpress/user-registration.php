<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'user_registration_notice', 50, 2);
}

if ( ! function_exists( 'user_registration_notice' ) ) {
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
}

/**
 * @return string
 * Add a notice for this integration
 */
if ( ! function_exists('rsssl_user_registration_notice' ) ) {
    function rsssl_user_registration_notice()
    {
        if ( get_option( 'users_can_register' ) ) {
            return 'can-register';
        }

        return false;
    }
}