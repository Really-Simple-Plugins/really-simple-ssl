<?php

add_action('template_redirect', 'rsssl_check_requests');

function rsssl_check_requests() {

	//XML-RPC
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
		add_action( 'xmlrpc_call', 'rsssl_handle_xmlrpc_request' );
	}
}