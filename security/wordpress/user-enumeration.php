<?php

/**
 * @return void
 * Update option to disable user enumeration
 */
function rsssl_disable_user_enumeration() {
    update_option('rsssl_disable_user_enumeration', true );
}

/**
 * @return void
 * Update option to enable user enumeration
 */
function rsssl_enable_user_enumeration() {
    update_option('rsssl_disable_user_enumeration', false );
}

// User Enumeration
function check_user_enumeration() {
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( rsssl_contains_numbers( $_REQUEST['author'] ) ) {
			wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'really-simple-ssl' ) . esc_html( $_REQUEST['author'] ) );
		}
	}
}

add_action('template_redirect', 'check_user_enumeration');