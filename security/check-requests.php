<?php

add_action('admin_init', 'rsssl_check_requests');

function rsssl_check_requests() {

	//XML-RPC
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
		error_log("xmlrpc call");
		add_action( 'xmlrpc_call', 'rsssl_handle_xmlrpc_request' );
	}

	// User Enumeration
	if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
		if ( rsssl_contains_numbers( $_REQUEST['author'] ) ) {

			wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'really-simple-ssl' ) . esc_html( $_REQUEST['author'] ) );
		}
	}
}