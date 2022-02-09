<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return bool
 * Check if application passwords are available
 */
function rsssl_application_passwords_available() {

	if ( wp_is_application_passwords_available() ) {
		return true;
	}

	return false;
}

/**
 * @return void
 * Disable application passwords
 */
function rsssl_disable_application_passwords() {
	add_filter( 'wp_is_application_passwords_available', '__return_false' );
}

/**
 * @return void
 * Enable application passwords
 */
function rsssl_enable_application_passwords() {
	add_filter( 'wp_is_application_passwords_available', '__return_true' );
}

add_action('admin_init', 'rsssl_application_passwords_available');
