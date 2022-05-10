<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'login_errors', 'rsssl_no_wp_login_errors' );

/**
 * @return string|void
 *
 * Override default login error message
 */
function rsssl_no_wp_login_errors()
{
    return __("Invalid login details.", "really-simple-ssl");
}
