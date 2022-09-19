<?php
defined('ABSPATH') or die();
/**
 * Override default login error message
 * @return string|void
 **/
function rsssl_no_wp_login_errors()
{
    return __("Invalid login details.", "really-simple-ssl");
}
add_filter( 'login_errors', 'rsssl_no_wp_login_errors' );

/**
 * Hide feedback entirely on password reset (no filter available).
 *
 * @return void
 *
 */
function rsssl_hide_pw_reset_error() {
    ?>
    <style>
       .login-action-lostpassword #login_error{
           display: none;
       }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'rsssl_hide_pw_reset_error' );

/**
 *
 * Clear username when username is valid but password is incorrect
 *
 * @return void
 */
function rsssl_clear_username_on_correct_username() {
    ?>
    <script>
        if ( document.getElementById('login_error') ) {
            document.getElementById('user_login').value = '';
        }
    </script>
    <?php
}
add_action( 'login_footer', 'rsssl_clear_username_on_correct_username' );
