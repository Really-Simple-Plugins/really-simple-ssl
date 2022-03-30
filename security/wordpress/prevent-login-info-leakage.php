<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'code_execution_uploads', 50, 3);
}

add_filter( 'login_errors', 'rsssl_no_wp_login_errors' );

/**
 * @return string|void
 *
 * Override default login error message
 */
function rsssl_no_wp_login_errors()
{
    return __("Invalid user and/or password.", "really-simple-ssl");
}
