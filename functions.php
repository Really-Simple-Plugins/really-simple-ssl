<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( !function_exists( 'rsssl_xmlrpc' ) ) {
	function rsssl_xmlrpc() {
		error_log("xmlrpc func");
	}
}
